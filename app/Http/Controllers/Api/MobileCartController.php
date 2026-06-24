<?php

namespace App\Http\Controllers\Api;

use App\Models\CheckOut;
use App\Models\MobileCart;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MobileCartController extends Controller
{
    //

    // public function store(Request $request)
    // {
    //     try {

    //         $userId = Auth::id();
    //         $listingId = $request->mobile_listing_id;
    //         $quantity = $request->quantity;

    //         // ✅ Get mobile listing with vendor + stock
    //         $mobile = VendorMobile::find($listingId);

    //         if (!$mobile) {
    //             return response()->json([
    //                 'message' => 'Mobile listing not found'
    //             ], 404);
    //         }

    //         // 🛑 Stop if requested quantity > stock
    //         if ($quantity > $mobile->stock) {
    //             return response()->json([
    //                 'message' => 'Quantity cannot be greater than available stock'
    //             ], 409);
    //         }


    //         // 🔍 Check all ACTIVE cart items of user
    //         $existingCarts = MobileCart::where('user_id', $userId)
    //             ->where('is_ordered', 0)
    //             ->get();


    //         // If user already has items in cart
    //         if ($existingCarts->count() > 0) {

    //             // Get the vendor of first cart item
    //             $currentVendorId = $existingCarts->first()->mobileListing->vendor_id;

    //             // 🛑 Block if trying to add item from DIFFERENT vendor
    //             if ($currentVendorId != $mobile->vendor_id) {
    //                 return response()->json([
    //                     'message' => 'Clear your cart to add items from a different vendor'
    //                 ], 409);
    //             }

    //             // 🛑 If SAME item already exists in cart
    //             $sameItem = $existingCarts->where('mobile_listing_id', $listingId)->first();

    //             if ($sameItem) {
    //                 return response()->json([
    //                     'message' => 'Item already added to cart. Please check your cart.'
    //                 ], 409);
    //             }
    //         }


    //         // ✅ Safe to add item
    //         $mobileCart = MobileCart::create([
    //             'user_id' => $userId,
    //             'mobile_listing_id' => $listingId,
    //             'quantity' => $quantity,
    //             'is_ordered' => 0
    //         ]);

    //         return response()->json([
    //             'message' => 'Mobile added to cart successfully',
    //             'data' => $mobileCart
    //         ], 200);


    //     } catch (\Exception $e) {

    //         return response()->json([
    //             'message' => 'Something went wrong!',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        try {

            $userId   = Auth::id();
            $listingId = $request->mobile_listing_id;
            $quantity  = (int) $request->quantity;
            // return $userId;
            // ✅ Validate basic input
            if (!$listingId || $quantity <= 0) {
                return response()->json([
                    'message' => 'Invalid listing or quantity'
                ], 422);
            }

            // ✅ Get mobile listing
            $mobile = VendorMobile::find($listingId);

            if (!$mobile) {
                return response()->json([
                    'message' => 'Mobile listing not found'
                ], 404);
            }

            // 🛑 Stock check
            if ($quantity > $mobile->stock) {
                return response()->json([
                    'message' => 'Quantity cannot be greater than available stock'
                ], 409);
            }

            // ✅ Get cart with relation (IMPORTANT FIX)
            $existingCarts = MobileCart::with('mobileListing')
                ->where('user_id', $userId)
                ->where('is_ordered', 0)
                ->get();

            if ($existingCarts->isNotEmpty()) {

                $firstCart = $existingCarts->first();

                // 🛑 Handle broken relation (NULL FIX)
                if (!$firstCart->mobileListing) {
                    return response()->json([
                        'message' => 'Some cart items are invalid. Please clear your cart.'
                    ], 409);
                }

                $currentVendorId = $firstCart->mobileListing->vendor_id;

                // 🛑 Different vendor check
                if ($currentVendorId != $mobile->vendor_id) {
                    return response()->json([
                        'message' => 'Clear your cart to add items from a different vendor'
                    ], 409);
                }

                // 🛑 Same item check
                $alreadyExists = $existingCarts
                    ->where('mobile_listing_id', $listingId)
                    ->first();

                if ($alreadyExists) {
                    return response()->json([
                        'message' => 'Item already added to cart. Please check your cart.'
                    ], 409);
                }
            }

            // ✅ Create cart item
            $mobileCart = MobileCart::create([
                'user_id' => $userId,
                'mobile_listing_id' => $listingId,
                'quantity' => $quantity,
                'is_ordered' => 0
            ]);

            return response()->json([
                'message' => 'Mobile added to cart successfully',
                'data' => $mobileCart
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


public function getCart(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    // Get cart with relations
    $carts = MobileCart::where('user_id', $user->id)
    ->where('is_ordered', 0)
        ->with([
            'mobileListing' => function($query) {
                $query->select('id','model_id','price','location','image','vendor_id','brand_id', 'stock');
            },
            'mobileListing.vendor:id,name',
            'mobileListing.brand:id,name',
            'mobileListing.model:id,name'
        ])
        ->get(['id','mobile_listing_id','quantity']);

    $finalData = [];

    $subtotal = 0;

    foreach ($carts as $cart) {

        $listing = $cart->mobileListing;

        // Convert stored JSON image to real URL
        $images = json_decode($listing->image, true);
        $imageUrl = isset($images[0]) ? asset($images[0]) : null;

        // price × quantity
        $totalPrice = ($listing->price ?? 0) * ($cart->quantity ?? 1);
        $subtotal += $totalPrice;

        $finalData[] = [
            'cart_id'   => $cart->id,
            'mobile_listing_id'=> $cart->mobile_listing_id,
            'listing_id'=> $listing->id,
            'price'     => $listing->price,
            'quantity'  => (int)$cart->quantity,
            'stock'     => $listing->stock,
            // 'total_price'=> $totalPrice,
            'location'  => $listing->location,
            'image'     => $imageUrl,
            'name' => $listing->vendor->name ?? null,
            'brand_name'  => $listing->brand->name ?? null,
            'model_name'  => $listing->model->name ?? null,
        ];
    }

    return response()->json([
        'message' => 'Cart details fetched successfully',
        'user_id' => $user->id,
        'data' => $finalData,
        'subtotal_price' => $subtotal,
    ], 200);
}


public function updateQuantity(Request $request)
{
    try {

        $userId = Auth::id();

        // 🛒 Find the cart item of this user that is not ordered yet
        $cart = MobileCart::where('id', $request->cart_id)
            ->where('user_id', $userId)
            ->where('is_ordered', 0)
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        // 📦 Fetch stock from vendor_mobiles
        $stock = $cart->mobileListing->stock ?? 0;

        // ❌ If requested quantity > stock
        if ($request->quantity > $stock) {
            return response()->json([
                'message' => 'Quantity cannot be greater than available stock'
            ], 422);
        }

        // ✅ Update quantity
        $cart->quantity = $request->quantity;
        $cart->save();

       
        return response()->json([
            'message' => 'Cart quantity updated successfully',
            'data' => [
                'id' => $cart->id,
                'mobile_listing_id' => $cart->mobile_listing_id,
                'quantity' => $cart->quantity
            ]
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => 'Something went wrong!',
            'error' => $e->getMessage()
        ], 500);
    }
}



   public function deleteCart(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $id = $request->query('id');

    // Find cart item
    $cart = MobileCart::where('id', $id)
        ->where('user_id', $user->id)
        ->with(['mobileListing.brand', 'mobileListing.model'])
        ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'Cart item not found',
        ], 404);
    }

    // ✅ Delete related checkout item
    CheckOut::where('user_id', $user->id)
        ->where('brand_name', $cart->mobileListing->brand->name ?? null)
        ->where('model_name', $cart->mobileListing->model->name ?? null)
        ->delete();

    // ✅ Delete cart item
    $cart->delete();

    return response()->json([
        'message' => 'Cart item and related checkout item deleted successfully',
    ], 200);
}

public function checkout(Request $request)
{
    try {

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$request->has('items') || !is_array($request->items)) {
            return response()->json([
                'message' => 'Invalid checkout data'
            ], 422);
        }

        $checkouts = [];

        foreach ($request->items as $item) {

            // ✅ product_id = vendor_mobiles.id
            $productId = $item['mobile_listing_id'] ?? null;

             // ✅ get vendor_id from vendor_mobiles
            $vendorId = null;
            if ($productId) {
                $vendorId = VendorMobile::where('id', $productId)
                    ->value('vendor_id');
            }

            $checkout = CheckOut::updateOrCreate(
                [
                    'user_id'    => $userId,
                    'vendor_id'  => $vendorId,
                    'product_id'=> $productId,
                ],
                [
                    'vendor_name' => $item['shop_name'] ?? null,
                    'brand_name'  => $item['brand_name'] ?? null,
                    'model_name'  => $item['model_name'] ?? null,
                    'price'       => $item['price'] ?? 0,
                    'location'    => $item['location'] ?? null,
                    'image'       => $item['image'] ?? null,
                    'quantity'    => $item['quantity'] ?? 1,
                ]
            );


            $checkouts[] = [
                // 'checkout_id' => $checkout->id,
                'product_id' => $checkout->product_id,
                'vendor_id'  => $checkout->vendor_id,
                'shop_name'   => $checkout->vendor_name,
                'brand_name'  => $checkout->brand_name,
                'model_name'  => $checkout->model_name,
                'price'       => $checkout->price,
                'quantity'    => $checkout->quantity,
                'image'       => $checkout->image,
            ];
        }

        return response()->json([
            'message' => 'Checkout completed successfully',
            'checkout_items' => $checkouts
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => 'Something went wrong!',
            'error' => $e->getMessage()
        ], 500);
    }
}




}
