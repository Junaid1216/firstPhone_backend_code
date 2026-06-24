<?php

namespace App\Services;

use App\Mail\VendorRequestForRegister;
use App\Models\SubscriptionPlan;
use App\Models\Vendor;
use App\Models\VendorImage;
use App\Models\VendorSubscription;
use App\Repositories\Interfaces\VendorRepositoryInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class VendorService
{
    protected $vendorRepo;

    public function __construct(VendorRepositoryInterface $vendorRepo)
    {
        $this->vendorRepo = $vendorRepo;
    }

    public function getAllUsers()
    {
        return $this->vendorRepo->all();
    }

    public function createUser($request)
    {
        $plainPassword = $request->password; 
        $data = $request->only(['name', 'email', 'phone', 'password', 'location', 'latitude', 'longitude']);
        $data['password'] = bcrypt($data['password']); // store hashed password

        $data['repair_service'] = $request->has('has_repairing') ? 1 : 0;

        // Profile Image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '_profile.' . $file->getClientOriginalExtension();
            $file->move(public_path('admin/assets/images/users/'), $filename);
            $data['image'] = 'public/admin/assets/images/users/' . $filename;
        } else {
            $data['image'] = 'public/admin/assets/images/default.png';
        }

        // CNIC Front
        if ($request->hasFile('cnic_front')) {
            $file = $request->file('cnic_front');
            $filename = uniqid() . '_cnic_front.' . $file->getClientOriginalExtension();
            $file->move(public_path('admin/assets/images/cnic/'), $filename);
            $data['cnic_front'] = 'public/admin/assets/images/cnic/' . $filename;
        }

        // CNIC Back
        if ($request->hasFile('cnic_back')) {
            $file = $request->file('cnic_back');
            $filename = uniqid() . '_cnic_back.' . $file->getClientOriginalExtension();
            $file->move(public_path('admin/assets/images/cnic/'), $filename);
            $data['cnic_back'] = 'public/admin/assets/images/cnic/' . $filename;
        }

        // Create Vendor
        $vendor = $this->vendorRepo->create($data);
        $plan = SubscriptionPlan::find(2);
        $start = now();
        $end = $start->copy()->addDays($plan->duration_days);
        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => 2,
            'start_date' => $start,
            'end_date' => $end,
            'is_active' => true,
        ]);

        // Save up to 5 shop images
        if ($request->hasFile('shop_images')) {
            $files = array_slice($request->file('shop_images'), 0, 5);
            foreach ($files as $index => $file) {
                $filename = uniqid() . '_shop_' . $index . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('admin/assets/images/shops/'), $filename);

                VendorImage::create([
                    'vendor_id' => $vendor->id,
                    'image' => 'public/admin/assets/images/shops/' . $filename,
                ]);
            }
        }

        // Attach plain password for email template
        $vendor->plain_password = $plainPassword;

        // Send email to vendor (with plain password)
        Mail::to($vendor->email)->send(new VendorRequestForRegister($vendor));

        return $vendor;
    }



    public function updateUser($id, $data)
    {
        $user = $this->vendorRepo->find($id);

        $user->repair_service = $data['repair_service'];

        // ===== Profile Image Upload =====
        if (isset($data['image']) && $data['image']->isValid()) {
            if ($user->image && File::exists($user->image)) {
                File::delete($user->image);
            }
            $file = $data['image'];
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_profile.' . $extension;
            $file->move('public/admin/assets/images/users', $filename);
            $data['image'] = 'public/admin/assets/images/users/' . $filename;
        } else {
            $data['image'] = $user->image;
        }

        // ===== CNIC Front Upload/Remove =====
        if (!empty($data['remove_cnic_front'])) {
            if ($user->cnic_front && File::exists($user->cnic_front)) {
                File::delete($user->cnic_front);
            }
            $data['cnic_front'] = null;
        } elseif (isset($data['cnic_front']) && $data['cnic_front']->isValid()) {
            if ($user->cnic_front && File::exists($user->cnic_front)) {
                File::delete($user->cnic_front);
            }
            $file = $data['cnic_front'];
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_cnic_front.' . $extension;
            $file->move('public/admin/assets/images/users', $filename);
            $data['cnic_front'] = 'public/admin/assets/images/users/' . $filename;
        } else {
            $data['cnic_front'] = $user->cnic_front;
        }

        // ===== CNIC Back Upload/Remove =====
        if (!empty($data['remove_cnic_back'])) {
            if ($user->cnic_back && File::exists($user->cnic_back)) {
                File::delete($user->cnic_back);
            }
            $data['cnic_back'] = null;
        } elseif (isset($data['cnic_back']) && $data['cnic_back']->isValid()) {
            if ($user->cnic_back && File::exists($user->cnic_back)) {
                File::delete($user->cnic_back);
            }
            $file = $data['cnic_back'];
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_cnic_back.' . $extension;
            $file->move('public/admin/assets/images/users', $filename);
            $data['cnic_back'] = 'public/admin/assets/images/users/' . $filename;
        } else {
            $data['cnic_back'] = $user->cnic_back;
        }

        // ===== Shop Images Upload =====
        if (isset($data['shop_images']) && is_array($data['shop_images'])) {
            $shopImages = $data['shop_images'];
            unset($data['shop_images']);

            foreach ($shopImages as $shopImage) {
                if ($shopImage->isValid()) {
                    $extension = $shopImage->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $shopImage->move('public/admin/assets/images/vendors', $filename);
                    $path = 'public/admin/assets/images/vendors/' . $filename;

                    VendorImage::create([
                        'vendor_id' => $user->id,
                        'image' => $path,
                    ]);
                }
            }
        }


        return $this->vendorRepo->update($id, $data);
    }


    public function deleteUser($id)
    {
        return $this->vendorRepo->delete($id);
    }

    public function findUser($id)
    {
        return $this->vendorRepo->find($id);
    }

    public function updateStatus($id, $status, $reason = null)
    {
        return $this->vendorRepo->updateStatus($id, $status, $reason);
    }

    public function pendingVendorCount()
    {
        return Vendor::where('status', 'pending')->count();
    }

    public function renewVendorPlan(int $vendorId, int $planId): VendorSubscription
    {
        $vendor = Vendor::findOrFail($vendorId);
        $plan = SubscriptionPlan::findOrFail($planId);

        VendorSubscription::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $start = now();
        $end = $start->copy()->addDays($plan->duration_days);

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $plan->id,
            'start_date' => $start,
            'end_date' => $end,
            'is_active' => true,
        ]);

        if ($plan->price == 0 && !$vendor->has_used_free_trial) {
            $vendor->update(['has_used_free_trial' => true]);
        }

        return $subscription;
    }
}
