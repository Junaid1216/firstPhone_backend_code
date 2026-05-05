<?php

namespace App\Repositories\Api;

use App\Models\SubscriptionPlan;
use App\Models\VendorSubscription;
use App\Repositories\Api\Interfaces\VendorSubscriptionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VendorSubscriptionRepository implements VendorSubscriptionRepositoryInterface
{

public function subscribe(Request $request)
{
    try {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $vendor = auth()->user();
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        // 🔹 Check if vendor ever used free plan
        $hasUsedFreePlan = $vendor->has_used_free_trial;

        // 🔹 Get active subscription (if any)
        $activeSubscription = VendorSubscription::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->where('end_date', '>=', now())
            ->with('plan')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 🚫 A. If trying to take free plan again after using it
        |--------------------------------------------------------------------------
        */
        if ($plan->price == 0 && $hasUsedFreePlan) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You have already used your free trial once.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | 🚫 B. If user has active paid plan and trying to switch to free
        |--------------------------------------------------------------------------
        */
        if ($activeSubscription && $activeSubscription->plan->price > 0 && $plan->price == 0) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You cannot switch to a free plan while having an active paid subscription.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | 🚫 C. If user already has active paid plan
        |--------------------------------------------------------------------------
        | 1. If new plan is cheaper or same price → block
        | 2. If new plan is more expensive → upgrade (allowed)
        |--------------------------------------------------------------------------
        */
        if ($activeSubscription && $activeSubscription->plan->price > 0 && $plan->price > 0) {
            // if ($plan->price <= $activeSubscription->plan->price) {
            //     return response()->json([
            //         'status' => 'forbidden',
            //         'message' => 'You cannot switch to a lower or same-tier paid plan while current one is active.',
            //     ], 403);
            // } else {
                // ✅ Allow upgrade → deactivate old plan
                $activeSubscription->update(['is_active' => false]);
            // }
        }

        /*
        |--------------------------------------------------------------------------
        | 🧹 Deactivate old active plan (if any other case)
        |--------------------------------------------------------------------------
        */

        if ($activeSubscription && $plan->price >= 0) {
            $activeSubscription->update(['is_active' => false]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🕒 Calculate subscription duration and create record
        |--------------------------------------------------------------------------
        */
        $start = now();
        $end = $start->copy()->addDays($plan->duration_days);
        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'subscription_plan_id' => $plan->id,
            'product_limit' => $plan->product_limit,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
            'plan_duration_days' => $plan->duration_days,
            'start_date' => $start,
            'end_date' => $end,
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 🏁 Mark free trial as used (if applicable)
        |--------------------------------------------------------------------------
        */
        if ($plan->price == 0 && !$vendor->has_used_free_trial) {
            $vendor->update(['has_used_free_trial' => true]);
        }

        /*
        |--------------------------------------------------------------------------
        | ✅ Final Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription activated successfully.',
            'data' => $subscription,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'server_error',
            'message' => 'Something went wrong: ' . $e->getMessage(),
        ], 500);
    }
}




    public function current(Request $request)
    {
        $vendor = auth()->user();

        $subscription = VendorSubscription::with(['plan:id,name,product_limit'])
            ->where('vendor_id', $vendor->id)
            // ->whereIn('is_active', [true, false])
            // ->where('end_date', '>=', now())
            ->latest('id') // ✅ explicitly by id
            ->first();

        if (!$subscription) {
            return response()->json(['status' => 'not_found','message' => 'No active subscription found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'subscription' => $subscription,
            // 'remaining_days' => now()->diffInDays($subscription->end_date, false),
            'remaining_days' => max(0, now()->diffInDays($subscription->end_date, false)),
        ]);
    }
}
