<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Requests\VendorRequest;
use App\Models\SubscriptionPlan;
use App\Models\UserRolePermission;
use App\Models\Vendor;
use App\Models\VendorImage;
use App\Services\VendorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class VendorController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function index()
    {
        // $users = $this->vendorService->getAllUsers();
        $sideMenuPermissions = collect();

        if (!Auth::guard('admin')->check()) {
            $user = Auth::guard('subadmin')->user()->load('roles');
            $permissions = UserRolePermission::with(['permission', 'sideMenue'])
                ->where('role_id', $user->role_id)
                ->get();
            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
                return $items->pluck('permission.name');
            });
        }

        return view('admin.vendor.index', compact('sideMenuPermissions'));
    }

    public function getVendorsData(Request $request)
{
    $sideMenuPermissions = collect();

    if (!Auth::guard('admin')->check()) {
        $user = Auth::guard('subadmin')->user();

        $roleId = $user->roles->first()->id ?? null;

        $permissions = UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get();

        $sideMenuPermissions = $permissions->groupBy('sideMenue.name')
            ->map(fn($items) => $items->pluck('permission.name'));
    }

    $query = Vendor::with(['subscription.plan', 'images'])
        ->latest();

    $subscriptionPlans = SubscriptionPlan::where('is_active', true)
        ->orderBy('price')
        ->get();

    return datatables()->of($query)
        ->addIndexColumn()

        ->filterColumn('created_at', function ($query, $keyword) {
            $query->whereRaw("DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') LIKE ?", ["%{$keyword}%"]);
        })

        ->filterColumn('package', function ($query, $keyword) {
            $query->whereHas('subscription.plan', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            });
        })

        // ✅ Date
        ->editColumn('created_at', function ($user) {
            return $user->created_at
                ? $user->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A')
                : '';
        })

        // ✅ Package
        ->addColumn('package', function ($user) {
            return $user->subscription && $user->subscription->plan
                ? $user->subscription->plan->name
                : '<span class="text-muted">No Package</span>';
        })

        ->addColumn('subscription_expiry', function ($user) {
            $subscription = $user->subscription;

            if (!$subscription || !$subscription->end_date) {
                return '<span class="text-muted">No active subscription</span>';
            }

            $endDate = Carbon::parse($subscription->end_date)->timezone('Asia/Karachi');
            $remainingDays = max(0, now()->diffInDays($endDate, false));
            $formattedDate = $endDate->format('d M Y');

            if ($remainingDays === 0 && $endDate->isPast()) {
                return '<span class="text-danger">'.$formattedDate.'</span><br><small class="text-muted">Expired</small>';
            }

            return $formattedDate.'<br><small class="text-success">'.$remainingDays.' day(s) remaining</small>';
        })

        ->addColumn('plan_renewal', function ($user) use ($sideMenuPermissions, $subscriptionPlans) {
            if (
                !Auth::guard('admin')->check() &&
                !($sideMenuPermissions->has('Vendors') && $sideMenuPermissions['Vendors']->contains('edit'))
            ) {
                return '<span class="text-muted">—</span>';
            }

            $currentPlanName = $user->subscription?->plan?->name ?? 'No Package';

            $html = '<select class="form-control form-control-sm renew-plan-select" data-vendor-id="'.$user->id.'" style="min-width: 140px;">
                <option value="" selected disabled>Plan Renewal</option>';

            foreach ($subscriptionPlans as $plan) {
                $html .= '<option value="'.$plan->id.'" data-plan-name="'.e($plan->name).'">'.e($plan->name).'</option>';
            }

            $html .= '</select>
                <small class="text-muted d-block mt-1">Current: '.e($currentPlanName).'</small>';

            return $html;
        })

        // ✅ Email
        ->editColumn('email', function ($user) {
            return '<a href="mailto:'.$user->email.'">'.$user->email.'</a>';
        })

        // ✅ Phone
        ->editColumn('phone', function ($user) {
            return '<a href="tel:'.$user->phone.'">'.$user->phone.'</a>';
        })

        // ✅ CNIC Front
        ->addColumn('cnic_front', function ($user) {
            return $user->cnic_front
                ? '<button class="btn btn-sm btn-info view-cnic"
                        data-front="'.asset($user->cnic_front).'"
                        data-back="'.asset($user->cnic_back).'">
                        <i class="fa fa-eye"></i>
                   </button>'
                : '<span class="text-muted">No CNIC Front</span>';
        })

        // ✅ CNIC Back
        ->addColumn('cnic_back', function ($user) {
            return $user->cnic_back
                ? '<button class="btn btn-sm btn-info view-cnic-back"
                        data-back="'.asset($user->cnic_back).'">
                        <i class="fa fa-eye"></i>
                   </button>'
                : '<span class="text-muted">No CNIC Back</span>';
        })

        // ✅ Shop Images
        ->addColumn('shop_images', function ($user) {
            if ($user->images && $user->images->count()) {
                return '<button class="btn btn-sm btn-info view-shop-images"
                    data-images=\''.json_encode($user->images->pluck('image')).'\'>
                    <i class="fa fa-eye"></i>
                </button>';
            }
            return '<span class="text-muted">No Shop Images</span>';
        })

        // ✅ Profile Image
        ->editColumn('image', function ($user) {
            return $user->image
                ? '<img src="'.asset($user->image).'" width="50" height="50">'
                :  '<img src="'.asset('public/admin/assets/images/default.png').'" width="50" height="50" alt="Default Image">';
        })

        // ✅ Status Dropdown
        ->addColumn('status', function ($user) use ($sideMenuPermissions) {

             if (
            Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Vendors') &&
            $sideMenuPermissions['Vendors']->contains('status'))
            )

            {

            $statusColors = [
                'pending' => 'btn-warning',
                'activated' => 'btn-primary',
                'deactivated' => 'btn-danger',
            ];

            $statusOptions = [
                'pending' => ['activated','deactivated'],
                'activated' => ['deactivated'],
                'deactivated' => ['activated']
            ];

            $html = '<div class="dropdown">
                <button class="btn btn-sm dropdown-toggle '.$statusColors[$user->status].'"
                    data-toggle="dropdown">'.ucfirst($user->status).'</button>
                <div class="dropdown-menu">';

            foreach ($statusOptions[$user->status] ?? [] as $status) {
                $html .= '<button class="dropdown-item change-vendor-status"
                    data-user-id="'.$user->id.'"
                    data-new-status="'.$status.'">'.ucfirst($status).'</button>';
            }

            $html .= '</div></div>';

            return $html;
            }
        })

        // ✅ Actions (Edit + Delete)
        ->addColumn('actions', function ($user) use ($sideMenuPermissions) {

            $buttons = '<div class="d-flex gap-1">';

        // ✅ EDIT BUTTON
        if (
            Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Vendors') &&
            $sideMenuPermissions['Vendors']->contains('edit'))
        ) {
            $buttons .= '
            <a href="'.route('vendor.edit',$user->id).'" class="btn btn-primary">
                <i class="fa fa-edit"></i>
            </a>';
        }

    //         if (
    //     Auth::guard('admin')->check() ||
    //     ($sideMenuPermissions->has('Vendors') &&
    //     $sideMenuPermissions['Vendors']->contains('delete'))
    // ) {
    //     $buttons .= '
    //     <form id="delete-form-'.$user->id.'" 
    //         action="'.route('vendor.delete',$user->id).'" 
    //         method="POST" style="display:inline;">
    //         '.csrf_field().'
    //         '.method_field('DELETE').'
    //     </form>

    //     <button class="show_confirm btn" 
    //         style="background-color: #009245;"
    //         data-form="delete-form-'.$user->id.'" 
    //         type="button">
    //         <i class="fa fa-trash"></i>
    //     </button>';
    // }

    $buttons .= '</div>';

    return $buttons;
        })

        ->rawColumns([
            'email','phone','cnic_front','cnic_back',
            'shop_images','image','status','actions','package',
            'subscription_expiry','plan_renewal'
        ])
        ->make(true);
}

    public function renewPlan(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Selected subscription plan is not active.',
            ], 422);
        }

        $subscription = $this->vendorService->renewVendorPlan(
            (int) $request->vendor_id,
            (int) $request->subscription_plan_id
        );

        $endDate = Carbon::parse($subscription->end_date)->timezone('Asia/Karachi')->format('d M Y');

        return response()->json([
            'success' => true,
            'message' => $plan->name.' plan renewed successfully for '.$plan->duration_days.' days.',
            'expiry_date' => $endDate,
            'remaining_days' => max(0, now()->diffInDays($subscription->end_date, false)),
            'plan_name' => $plan->name,
        ]);
    }

    public function vendorpendingCounter()
    {
        $count = $this->vendorService->pendingVendorCount();
        return response()->json(['count' => $count]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:vendors,id',
            'status' => 'required|in:pending,activated,deactivated',
            'reason' => 'nullable|string|max:255',
        ]);

        $vendor = $this->vendorService->updateStatus(
            $request->id,
            $request->status,
            $request->reason
        );

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully',
            'new_status' => ucfirst($vendor->status),
        ]);
    }


    public function createView()
    {
        return view('admin.vendor.create');
    }

    public function create(VendorRequest $request)
    {
        $this->vendorService->createUser($request);
        return redirect()->route('vendor.index')->with('success', 'Vendor Created Successfully');
    }

    public function edit($id)
    {
        $user = $this->vendorService->findUser($id);
        return view('admin.vendor.edit', compact('user'));
    }

    public function update(UpdateVendorRequest $request, $id)
    {
        $data = $request->only([
            'name',
            'email',
            'phone',
            'location',
        ]);

        $data['repair_service'] = $request->has('has_repairing') ? 1 : 0;

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        if ($request->hasFile('cnic_front')) {
            $data['cnic_front'] = $request->file('cnic_front');
        }

        if ($request->hasFile('cnic_back')) {
            $data['cnic_back'] = $request->file('cnic_back');
        }

        if ($request->hasFile('shop_images')) {
            $data['shop_images'] = $request->file('shop_images');
        }

        $this->vendorService->updateUser($id, $data);

        return redirect('/admin/vendor')->with('success', 'Vendor Updated Successfully');
    }



    public function delete($id)
    {
        $deleted = $this->vendorService->deleteUser($id);
        return redirect()->back()->with($deleted ? 'success' : 'error', $deleted ? 'Vendor Deleted Successfully' : 'User Not Found');
    }
}
