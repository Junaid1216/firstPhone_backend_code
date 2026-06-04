<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\CheckOut;
use App\Models\OrderItem;
use App\Models\MobileCart;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Mail\OrderPlaced;
use App\Models\Notification;
use App\Models\NotificationTarget;
use Illuminate\Support\Facades\Mail;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;


class OnlinePaymentController extends Controller
{
    public function placeOrder(Request $request)
    {
        // dd($request->all());

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            // $products = $request->products;
            $products = $request->products ?? $request->all();

            // Sequential Order Number
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $newOrderNumber = $lastOrder ? $lastOrder->order_number + 1 : 10000000;

            $customer = auth()->user();

            // Create Order
            $order = '';
            if($request->delivery_method == 'Online'){
                  $order = Order::create([
                    'customer_id'      => $customer->id,
                    'customer_name'    => $customer->name,
                    'customer_email'   => $customer->email,
                    'customer_phone'   => $customer->phone,
                    'order_number'     => $newOrderNumber,
                    'payment_status'   => 'paid',
                    'order_status'     => 'inprogress',
                    'delivery_method'  => $request->delivery_method,
                    'shipping_charges'  => $request->shipping_charges,
                    'created_at' => Carbon::now('Asia/Karachi'),
                ]);
            } else {
                    $order = Order::create([
                    'customer_id'      => $customer->id,
                    'customer_name'    => $customer->name,
                    'customer_email'   => $customer->email,
                    'customer_phone'   => $customer->phone,
                    'order_number'     => $newOrderNumber,
                    'payment_status'   => 'unpaid',
                    'order_status'     => 'inprogress',
                    'delivery_method'  => $request->delivery_method,
                    'shipping_charges'  => $request->shipping_charges,
                    'created_at' => Carbon::now('Asia/Karachi'),
                    ]);
            }

            $vendorIds = [];
            $orderedListingIds = [];

            // Handle Single Product
            if (isset($products['product_id'])) {
                $orderedListingIds[] = $products['product_id'];
                $vendorIds[] = $products['vendor_id'];
                $productData = VendorMobile::with(['brand','model','vendor'])->find($products['product_id']);
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $products['product_id'],
                    'vendor_id'  => $products['vendor_id'],
                    'vendor_name'  => $productData->vendor->name ?? null,
                    'vendor_email' => $productData->vendor->email ?? null,
                    'vendor_phone' => $productData->vendor->phone ?? null,
                    'quantity'   => $products['quantity'] ?? 1,
                    'price'      => $products['price'],

                   'brand_name'   => $productData->brand->name ?? null,
                    'model_name'   => $productData->model->name ?? null,
                    'location'     => $productData->location ?? null,
                    'latitude'     => $productData->latitude ?? null,
                    'longitude'    => $productData->longitude ?? null,
                    'storage'      => $productData->storage ?? null,
                    'ram'          => $productData->ram ?? null,
                    'color'        => $productData->color ?? null,
                    'condition'    => $productData->condition ?? null,
                    'processor'    => $productData->processor ?? null,
                    'display'      => $productData->display ?? null,
                    'charging'     => $productData->charging ?? null,
                    'refresh_rate' => $productData->refresh_rate ?? null,
                    'main_camera'  => $productData->main_camera ?? null,
                    'ultra_camera' => $productData->ultra_camera ?? null,
                    'telephoto_camera' => $productData->telephoto_camera ?? null,
                    'front_camera' => $productData->front_camera ?? null,
                    'build'        => $productData->build ?? null,
                    'wireless'     => $productData->wireless ?? null,
                    'stock'        => $productData->stock ?? null,
                    'sold'         => $productData->sold ?? null,
                    'pta_approved' => $productData->pta_approved ?? null,
                    'ai_features'  => $productData->ai_features ?? null,
                    'battery_health' => $productData->battery_health ?? null,
                    'os_version'   => $productData->os_version ?? null,
                    'warranty_start' => $productData->warranty_start ?? null,
                    'warranty_end' => $productData->warranty_end ?? null,

                    'image' => $productData->image ? json_encode(json_decode($productData->image, true)) : null,
                    'video' => $productData->video ? json_encode(json_decode($productData->video, true)) : null,

                    'about' => $productData->about ?? null,
                ]);
                $totalAmount = $products['price'] * ($products['quantity'] ?? 1);
                VendorMobile::where('id', $products['product_id'])->decrement('stock', $products['quantity'] ?? 1);
            } else {
                // Handle Multiple Products
                foreach ($products as $product) {
                    $vendorIds[] = $product['vendor_id'];
                    $orderedListingIds[] = $product['product_id'];
                    $productData = VendorMobile::with(['brand','model','vendor'])->find($products['product_id']);
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product['product_id'],
                        'vendor_id'  => $product['vendor_id'],
                        'vendor_name'  => $productData->vendor->name ?? null,
                        'vendor_email' => $productData->vendor->email ?? null,
                        'vendor_phone' => $productData->vendor->phone ?? null,
                        'quantity'   => $product['quantity'] ?? 1,
                        'price'      => $product['price'],

                       'brand_name'   => $productData->brand->name ?? null,
                    'model_name'   => $productData->model->name ?? null,
                    'location'     => $productData->location ?? null,
                    'latitude'     => $productData->latitude ?? null,
                    'longitude'    => $productData->longitude ?? null,
                    'storage'      => $productData->storage ?? null,
                    'ram'          => $productData->ram ?? null,
                    'color'        => $productData->color ?? null,
                    'condition'    => $productData->condition ?? null,
                    'processor'    => $productData->processor ?? null,
                    'display'      => $productData->display ?? null,
                    'charging'     => $productData->charging ?? null,
                    'refresh_rate' => $productData->refresh_rate ?? null,
                    'main_camera'  => $productData->main_camera ?? null,
                    'ultra_camera' => $productData->ultra_camera ?? null,
                    'telephoto_camera' => $productData->telephoto_camera ?? null,
                    'front_camera' => $productData->front_camera ?? null,
                    'build'        => $productData->build ?? null,
                    'wireless'     => $productData->wireless ?? null,
                    'stock'        => $productData->stock ?? null,
                    'sold'         => $productData->sold ?? null,
                    'pta_approved' => $productData->pta_approved ?? null,
                    'ai_features'  => $productData->ai_features ?? null,
                    'battery_health' => $productData->battery_health ?? null,
                    'os_version'   => $productData->os_version ?? null,
                    'warranty_start' => $productData->warranty_start ?? null,
                    'warranty_end' => $productData->warranty_end ?? null,

                    'image' => $productData->image ? json_encode(json_decode($productData->image, true)) : null,
                    'video' => $productData->video ? json_encode(json_decode($productData->video, true)) : null,

                    'about' => $productData->about ?? null,
                    ]);

                    $totalAmount += $product['price'] * ($product['quantity'] ?? 1);
                    VendorMobile::where('id', $product['product_id'])->decrement('stock', $product['quantity'] ?? 1);
                }
            }
            $totalAmount += $request->shipping_charges;
            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            MobileCart::where('user_id', auth()->id())
            ->whereIn('mobile_listing_id', $orderedListingIds)
            ->update(['is_ordered' => 1]);

            // Notify all unique vendors
                $notification = Notification::create([
                    'user_type' => 'vendors',
                    'title' => "New Order Received",
                    'description' => "You have received a new order #{$order->order_number}, Total: Rs {$totalAmount}",
                ]);
            foreach (array_unique($vendorIds) as $vendorId) {
                $vendor = \App\Models\Vendor::find($vendorId);
                NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $vendorId,
                    'targetable_type' => 'App\Models\Vendor',
                    'type' => 'order_placed',
                ]);
                if ($vendor && !empty($vendor->fcm_token)) {
                    \App\Helpers\NotificationHelper::sendFcmNotification(
                        $vendor->fcm_token,
                        "New Order Received",
                        "You have received a new order #{$order->order_number}, Total: Rs {$totalAmount}",
                        [
                            'type' => 'order_placed',
                            'order_id'     => (string) $order->id,
                            'order_number' => (string) $order->order_number,
                            'total_amount' => (string) $totalAmount,
                        ]
                    ); 
                }
            }
            $vendor = \App\Models\Vendor::find($vendorIds[0]);
            Mail::to($vendor->email)->send(new OrderPlaced($vendor->name, $order->order_number));
            DB::commit();

            CheckOut::where('user_id', auth()->id())->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Order placed successfully & vendors notified',
                'data'    => [
                    'order' => $order,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong!',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function deliveryMethods()
{
    return response()->json([
        'message' => 'Delivery methods fetched successfully',
        'data' => [
            [
                'key'   => 'go_shop',
                // 'label' => 'Go to Shop'
            ],
            [
                'key'   => 'cod',
                // 'label' => 'Cash on Delivery'
            ],
            [
                'key'   => 'online',
                // 'label' => 'Online Payment'
            ],
        ]
    ], 200);
}

}
