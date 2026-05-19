<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\Admin;
use App\Models\AuthorizedDealer;
use App\Models\EnsuredCrop;
use App\Models\Farmer;
use App\Models\Order;
use App\Models\SideMenu;
use App\Models\SubAdmin;
use App\Models\SubAdminPermission;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{

    public function getdashboard()
    {
        $totalCustomers = User::count();
        $totalVendors   = Vendor::count();

        $activeOrders   = Order::whereIn('order_status', ['confirmed', 'in_progress', 'shipped'])->count();
        $pendingOrders  = Order::where('order_status', 'pending')->count();
        $cancelledOrders = Order::where('order_status', 'cancelled')->count();

        return view('admin.index', compact(
            'totalCustomers',
            'totalVendors',
            'activeOrders',
            'pendingOrders',
            'cancelledOrders'
        ));
    }

    public function getProfile()
    {
        if (Auth::guard('admin')->check()) {
            $data = Admin::find(Auth::guard('admin')->id());
        } elseif (Auth::guard('subadmin')->check()) {
            $data = SubAdmin::find(Auth::guard('subadmin')->id());
        } else {
            // Not authenticated — optionally redirect to login
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        return view('admin.auth.profile', compact('data'));
    }

    public function update_profile(Request $request)

    {

        // $request->validate([

        //     'name' => 'required',

        //     'email' => 'required',

        //     'phone' => 'required'

        // ]);

        $data = $request->only(['name', 'email', 'phone']);



        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $extension = $file->getClientOriginalExtension();

            $filename = time() . '.' . $extension;

            $file->move('public/admin/assets/images/admin', $filename);

            $data['image'] = 'public/admin/assets/images/admin/' . $filename;
        }

        if (Auth::guard('admin')->check()) {

            Admin::find(Auth::guard('admin')->id())->update($data);
        } else {

            SubAdmin::find(Auth::guard('subadmin')->id())->update($data);
        }

        return back()->with('success', 'Profile Updated Successfully');
    }

    public function profile_change_password(Request $request)
{
    $request->validate([
        'new_password' => 'required',
        'new_password_confirmation' => 'required|same:new_password',
    ],[
        'new_password.required' => 'Please enter new password',
        'new_password_confirmation.required' => 'Please confirm password',
        'new_password_confirmation.same' => 'Confirm password does not match',
    ]);

    $user = Auth::guard('admin')->user();

    // Prevent same password reuse
    if (Hash::check($request->new_password, $user->password)) {

        return back()->with([
            'status' => false,
            'error' => 'New Password Cannot Be Same As Old Password'
        ]);
    }

    $user->password = Hash::make($request->new_password);
    $user->save();

    return back()->with([
        'status' => true,
        'success' => 'Password Updated Successfully'
    ]);
}

    public function forgetPassword()
    {
        return view('admin.auth.forgetPassword');
    }
    public function adminResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $adminExists = DB::table('admins')->where('email', $request->email)->first();

        if (!$adminExists) {
            $subAdminExists = DB::table('sub_admins')->where('email', $request->email)->first();
        }

        if (!$adminExists && !$subAdminExists) {
            return back()->withErrors(['email' => 'The Email Adress Is Not Registered With Any Admin Or Subadmin']);
        }

        $emailToUse = $adminExists ? $adminExists->email : $subAdminExists->email;

        $exists = DB::table('password_resets')->where('email', $emailToUse)->first();

        // $exists = DB::table('password_resets')->where('email', $request->email)->first();
        if ($exists) {
            return back()->with('error', 'Reset Password Link Has Been Already Sent');
            // dd($subAdminExists);
        } else {
            $token = Str::random(30);
            DB::table('password_resets')->insert([
                'email' => $emailToUse,
                'token' => $token,
            ]);

            $data['url'] = url('change_password', $token);
            // Mail::to($emailToUse)->send(new ResetPasswordMail($data));
            return back()->with('success', 'Reset Password Link Send Successfully');
        }
    }
    public function change_password($id)
    {

        $user = DB::table('password_resets')->where('token', $id)->first();

        if (isset($user)) {
            return view('admin.auth.chnagePassword', compact('user'));
        }
    }

    public function resetPassword(Request $request)
    {

        $request->validate([
            'password' => 'required|min:8|unique:admins,password|unique:sub_admins,password',
            'confirmPassword' => 'required',

        ]);
        // return $request;
        if ($request->password != $request->confirmPassword) {
            // return $request;
            return back()->with('error', 'Password And Confirm Password Does Not Match');
        }
        $password = bcrypt($request->password);
        $adminExists = Admin::where('email', $request->email)->first();

        if (!$adminExists) {
            $subAdminExists = SubAdmin::where('email', $request->email)->first();
        }

        if (!$adminExists && !$subAdminExists) {
            return back()->with('error', 'Email address not found');
        }

        if ($adminExists) {
            $adminExists->update(['password' => $password]);
        } elseif ($subAdminExists) {
            $subAdminExists->update(['password' => $password]);
        }

        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect('/admin')->with('success', 'Password Updated Successfully');
    }


    public function logout()
    {
        $adminExists = Auth::guard('admin')->logout();
        // dd($adminExists);
        if (!$adminExists) {
            Auth::guard('subadmin')->logout();
        }
        return redirect('admin')->with('success', 'Logged Out Successfully');
    }


    public function getSubAdminPermissions()
    {
        $subadmin = Auth::guard('subadmin')->user();

        // Fetch sub-admin permissions with associated side menus
        $sidemenu_permission = SubAdminPermission::where('sub_admin_id', $subadmin->id)
            ->whereIn('permissions', ['view', 'create', 'edit', 'delete'])
            ->with('side_menu')
            ->get();

        // Extract unique side menu names
        $sideMenuName = $sidemenu_permission->pluck('side_menu.name')->unique();

        // Group and map permissions by side menu name
        $sideMenuPermissions = $sidemenu_permission
            ->groupBy(fn($permission) => $permission->side_menu->name) // Group by side menu name
            ->map(function ($group, $sideMenuName) {
                return [
                    'side_menu_name' => $sideMenuName,
                    'permissions' => $group->pluck('permissions')->unique(),
                ];
            });

        return [
            'sideMenuPermissions' => $sideMenuPermissions,
            'sideMenuName' => $sideMenuName,
        ];
    }
}
