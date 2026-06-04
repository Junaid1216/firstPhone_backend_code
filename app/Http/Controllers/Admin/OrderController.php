<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Middleware\admin;
use App\Models\CancelOrder;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\Order;
use App\Models\UserRolePermission;
use App\Repositories\Interfaces\OrderRepoInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderRepo;

    public function __construct(OrderRepoInterface $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    public function index()
    {
        // $orders = $this->orderRepo->getAllOrders();
        $statuses = ['inprogress', 'shipped', 'delivered', 'cancelled'];

        $totals = $this->orderRepo->getTotals();

        return view('admin.order.index', [
            // 'orders'      => $orders,
            'statuses'    => $statuses,
            'total'       => $totals['total'],
            'codTotal'    => $totals['codTotal'],
            'onlineTotal' => $totals['onlineTotal'],
            'pickupTotal' => $totals['pickupTotal'],
        ]);
    }

    public function getOrdersData(Request $request)
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

    $query = Order::with(['customer','items.vendor','items.product.brand','items.product.model'])
        ->latest();

    return datatables()->of($query)

        ->addIndexColumn()

        // 📅 Date
        ->editColumn('created_at', function ($order) {
            return $order->created_at
                ? $order->created_at->format('d M Y, h:i A')
                : '';
        })

        // 🆔 Order ID
        ->addColumn('order_number', function ($order) {
            return '#'.$order->order_number;
        })

        // 👤 Customer
        ->addColumn('customer', function ($order) {
            $name = $order->customer->name ?? $order->customer_name;
            $email = $order->customer->email ?? $order->customer_email;
            $phone = $order->customer->phone ?? $order->customer_phone;

            return $name.'<br>
            <small><a href="mailto:'.$email.'">'.$email.'</a></small><br>
            <small><a href="tel:'.$phone.'">'.$phone.'</a></small>';
        })

        // 🏪 Vendor (Buy From)
        ->addColumn('vendor', function ($order) {

            $item = $order->items->first();

            if (!$item) return 'No Vendor';

            $vendorName  = $item->vendor->name ?? $item->vendor_name;
            $vendorEmail = $item->vendor->email ?? $item->vendor_email;
            $vendorPhone = $item->vendor->phone ?? $item->vendor_phone;

            return $vendorName.'<br>
                <small><a href="mailto:'.$vendorEmail.'">'.$vendorEmail.'</a></small><br>
                <small><a href="tel:'.$vendorPhone.'">'.$vendorPhone.'</a></small>';
        })

        // 📱 Products
        ->addColumn('products', function ($order) {

            $html = '';

            foreach ($order->items as $item) {

                $brand = $item->product->brand->name ?? $item->brand_name;
                $model = $item->product->model->name ?? $item->model_name;

                $html .= $brand.' '.$model.'<br>
                (Qty: '.$item->quantity.', Price: '.number_format($item->price,2).')<br>';
            }

            return $html;
        })

        // 💰 Total Price
        ->addColumn('total_price', function ($order) {
            $total = $order->items->sum(fn($item) => $item->price * $item->quantity);
            return number_format($total);
        })

        // 🚚 Shipping
        ->addColumn('shipping', function ($order) {
            return $order->shipping_charges
                ? number_format($order->shipping_charges)
                : '<span class="text-muted">No Shipping Charges</span>';
        })

        // 💳 Payment Status
        ->addColumn('payment_status', function ($order) {

            $colors = [
                'paid' => 'bg-success',
                'unpaid' => 'bg-warning',
            ];

            $class = $colors[$order->payment_status] ?? 'bg-secondary';

            return '<span class="badge '.$class.'">'
                .ucfirst(str_replace('_',' ',$order->payment_status)).
                '</span>';
        })

        // 🚚 Delivery Method
        ->addColumn('delivery_method', function ($order) {

            $labels = [
                'cod' => 'COD',
                'online' => 'Online',
                'go_shop' => 'GoShop',
            ];

            $classes = [
                'cod' => 'bg-warning',
                'online' => 'bg-primary',
                'go_shop' => 'bg-info',
            ];

            $class = $classes[$order->delivery_method] ?? 'bg-secondary';

            return '<span class="badge '.$class.'">'
                .($labels[$order->delivery_method] ?? ucwords(str_replace('_',' ',$order->delivery_method)))
                .'</span>';
        })

        // 📦 Order Status
        ->addColumn('order_status', function ($order) {

            $colors = [
                'inprogress' => 'bg-warning',
                'shipped' => 'bg-secondary',
                'delivered' => 'bg-primary',
                'cancelled' => 'bg-danger',
            ];

            $class = $colors[$order->order_status] ?? 'bg-light';

            return '<span class="badge '.$class.'">'
                .ucfirst(str_replace('_',' ',$order->order_status)).
                '</span>';
        })

                ->filterColumn('order_number', fn($query, $keyword) => $query->where('order_number','like',"%{$keyword}%"))
                 ->filterColumn('created_at', function($query, $keyword) {
                    $query->whereRaw(
                        "DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') LIKE ?",
                        ["%{$keyword}%"]
                    );
                })
                ->filterColumn('customer', function($query, $keyword) {
                    $query->whereHas('customer', fn($q) => $q->where('name','like',"%{$keyword}%")
                                                        ->orWhere('email','like',"%{$keyword}%")
                                                        ->orWhere('phone','like',"%{$keyword}%"));
                })
                ->filterColumn('vendor', function($query, $keyword) {
                    $query->whereHas('items.vendor', fn($q) => $q->where('name','like',"%{$keyword}%")
                                                                ->orWhere('email','like',"%{$keyword}%")
                                                                ->orWhere('phone','like',"%{$keyword}%"));
                })
               ->filterColumn('products', function($query, $keyword) {
                $query->whereHas('items.product', function($q) use ($keyword) {
                    $q->where(function($q2) use ($keyword) {
                        $q2->whereHas('brand', fn($b) => $b->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('model', fn($m) => $m->where('name', 'like', "%{$keyword}%"));
                    });
                });
            })
                ->filterColumn('total_price', fn($query, $keyword) => $query->whereHas('items', fn($q) => $q->whereRaw('price * quantity like ?', ["%{$keyword}%"])))
                ->filterColumn('shipping', fn($query, $keyword) => $query->where('shipping_charges','like',"%{$keyword}%"))
                ->filterColumn('payment_status', function($query, $keyword) {
                    $query->where('payment_status', 'like', "%{$keyword}%");
                })
                ->filterColumn('delivery_method', function($query, $keyword) {
                    $query->where('delivery_method', 'like', "%{$keyword}%");
                })
                ->filterColumn('order_status', function($query, $keyword) {
                    $query->where('order_status', 'like', "%{$keyword}%");
                })

        ->rawColumns([
            'customer','vendor','products',
            'shipping','payment_status',
            'delivery_method','order_status'
        ])

        ->make(true);
}


    public function destroy($id)
    {
        $this->orderRepo->deleteOrder($id);
        return redirect()->route('order.index')->with('success', 'Order deleted successfully');
    }

    public function updateStatus(Request $request, $id)
    {
        $order = $this->orderRepo->updateOrderStatus($request, $id);

        $vendor = $order->vendor;
        $notification = Notification::create([
                    'user_type' => 'vendors',
                    'title' => "Order Status Updated",
                    'description' => "Your mobile listing is under review. We'll notify you once approved.",
                ]);
        NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $customerId,
                    'targetable_type' => 'App\Models\User',
                    'type' => 'order_status_updated',
                ]);
        if ($vendor && $vendor->fcm_token) {
            NotificationHelper::sendFcmNotification(
                $vendor->fcm_token,
                "Order Status Updated",
                "Order #{$order->id} status changed to {$order->order_status}.",
                [
                    'type' => 'order_status_updated',
                    'order_id' => $order->id,
                    'new_status' => $order->order_status
                ]
            );
        }

        return response()->json([
            'success' => true,
            'new_status' => $order->order_status,
            'message' => 'Order status updated successfully'
        ]);
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $this->orderRepo->updatePaymentStatus($request, $id);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
        ]);
    }

    public function pendingCounter()
    {
        $count = $this->orderRepo->pendingOrdersCount();
        return response()->json(['count' => $count]);
    }

    public function getTotals()
    {
        $total = $this->orderRepo->getTotals();
        return response()->json($total);
    }

    public function cancel_order()
    {
        // $cancelOrders = CancelOrder::with([
        //     'order.customer',
        //     'orderItem.vendor'
        // ])
        // ->whereHas('order', function ($query) {
        // $query->where('delivery_method', 'online');
        // })
        // ->latest()
        // ->get();

        return view('admin.order.cancel');
    }

    public function getCancelOrdersData(Request $request)
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

    $query = CancelOrder::with([
            'order.customer',
            'orderItem.vendor',
            'orderItem.product.brand',
            'orderItem.product.model'
        ])
        ->whereHas('order', function ($q) {
            $q->where('delivery_method', 'online');
        })
        ->latest();

    return datatables()->of($query)

        ->addIndexColumn()

        // 📅 Date
        ->editColumn('created_at', function ($c) {
            return $c->created_at
                ? $c->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A')
                : '';
        })

        // 🆔 Order ID
        ->addColumn('order_id', function ($c) {
            return '#'.($c->order->order_number ?? '-');
        })

        // 📱 Product
        ->addColumn('order_item', function ($c) {
            $brand = $c->orderItem->product->brand->name ?? '-';
            $model = $c->orderItem->product->model->name ?? '-';
            return $brand.' - '.$model;
        })

        // 🏪 Vendor
        ->addColumn('vendor', function ($c) {
            return $c->orderItem->vendor->name
            ?? $c->orderItem->vendor_name
            ?? '-';
        })

        // 📝 Reason
        ->addColumn('reason', fn($c) => $c->reason ?? '')

        // 🚚 Delivery Method
        ->addColumn('delivery_method', function ($c) {

            $method = $c->order->delivery_method ?? '';

            return match ($method) {
                'cod' => '<span class="badge bg-warning">COD</span>',
                'online' => '<span class="badge bg-primary">Online</span>',
                'pickup' => '<span class="badge bg-info">GoShop</span>',
                default => '<span class="badge bg-secondary">'.ucfirst($method).'</span>',
            };
        })

        // 📎 Proof
        ->addColumn('proof', function ($c) {
            if ($c->proof_file_image) {
                return '<button class="btn btn-sm btn-info view-proof"
                    data-front="'.asset('public/'.$c->proof_file_image).'">
                    View
                </button>';
            }
            return '<span class="text-muted">No Proof</span>';
        })

        // 🚦 Status (FULL DROPDOWN LOGIC)
        ->addColumn('status', function ($c) use ($sideMenuPermissions) {

            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Cancel Orders') &&
                $sideMenuPermissions['Cancel Orders']->contains('status'))
            ) {

                $colors = [
                    'requested' => 'btn-warning',
                    'approved' => 'btn-success',
                    'rejected' => 'btn-danger',
                ];

                // ✅ Approved → only badge
                if ($c->status === 'approved') {
                    return '<span class="btn btn-success btn-sm">Approved</span>';
                }

                $html = '<div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle '.$colors[$c->status].'"
                        data-toggle="dropdown">'
                        .ucfirst($c->status).'
                    </button>
                    <div class="dropdown-menu">';

                // requested → both options
                if ($c->status === 'requested') {
                    $html .= '
                        <button class="dropdown-item change-cancel-status"
                            data-id="'.$c->id.'" data-new-status="approved">
                            Approved
                        </button>

                        <button class="dropdown-item change-cancel-status"
                            data-id="'.$c->id.'" data-new-status="rejected">
                            Rejected
                        </button>';
                }

                // rejected → only approve
                if ($c->status === 'rejected') {
                    $html .= '
                        <button class="dropdown-item change-cancel-status"
                            data-id="'.$c->id.'" data-new-status="approved">
                            Approved
                        </button>';
                }

                $html .= '</div></div>';

                return $html;
            }

            return '';
        })

        // ⚙️ Actions
        ->addColumn('action', function ($c) use ($sideMenuPermissions) {

            $btn = '';

            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Cancel Orders') &&
                $sideMenuPermissions['Cancel Orders']->contains('delete'))
            ) {

                $btn .= '
                <form id="delete-form-'.$c->id.'"
                    action="'.route('cancel-orders.destroy',$c->id).'"
                    method="POST">
                    '.csrf_field().method_field('DELETE').'
                </form>

                <button class="show_confirm btn"
                    style="background-color:#009245"
                    data-form="delete-form-'.$c->id.'">
                    <i class="fa fa-trash"></i>
                </button>';
            }

            return $btn;
        })

        ->filterColumn('created_at', function($query, $keyword) {
        $query->whereRaw("DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('order_id', fn($query, $keyword) =>
                $query->whereHas('order', fn($q) => $q->where('order_number', 'like', "%{$keyword}%"))
            )
            ->filterColumn('order_item', fn($query, $keyword) =>
                $query->whereHas('orderItem.product', fn($q) => 
                    $q->whereHas('brand', fn($b) => $b->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('model', fn($m) => $m->where('name', 'like', "%{$keyword}%"))
                )
            )
            ->filterColumn('vendor', fn($query, $keyword) =>
                $query->whereHas('orderItem.vendor', fn($q) =>
                    $q->where('name', 'like', "%{$keyword}%")
                )
            )
            ->filterColumn('reason', fn($query, $keyword) =>
                $query->where('reason', 'like', "%{$keyword}%")
            )

            ->filterColumn('status', fn($query, $keyword) =>
                $query->where('status', 'like', "%{$keyword}%")
            )

        ->rawColumns([
            'delivery_method','proof','status','action'
        ])

        ->make(true);
}

    // public function updateCancelStatus(Request $request, $id)
    // {
    //     $cancelOrder = CancelOrder::findOrFail($id);

    //     if ($request->hasFile('proof_file_image')) {
    //         $file = $request->file('proof_file_image');
    //         $filename = time() . '_' . $file->getClientOriginalName();
    //         $file->move(public_path('uploads/cancel_proofs'), $filename);
    //         $cancelOrder->proof_file_image = 'uploads/cancel_proofs/' . $filename;
    //     }

    //     $cancelOrder->status = $request->status;
    //     $cancelOrder->save();

    //     if ($request->status === 'approved') {
    //     $order = Order::findOrFail($cancelOrder->order_id);
    //     $order->order_status = 'cancelled';
    //     $order->save();

    //     }  

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Cancel order status updated successfully'
    //     ]);
    // }

    // public function checkDeliveryStatus($id)
    // {
    //     // $cancelOrder = CancelOrder::with('order')->findOrFail($id);
    //     $cancelOrder = CancelOrder::with([
    //         'order.customer',
    //         'order.items.vendor'
    //     ])->findOrFail($id);

    //     if ($cancelOrder->order->delivery_method === 'online') {

    //         $order = $cancelOrder->order;
  
    //         $notifications = [
    //             [
    //                 'user_type' => 'vendors',
    //                 'targetable_id' => $order->items->first()->vendor->id ?? null,
    //                 'targetable_type' => 'App\Models\Vendor',
    //                 'token' => $order->items->first()->vendor->fcm_token ?? null,
    //                 'title' => "Order Cancellation Approved",
    //                 'message' => "Your cancellation request for order #{$order->order_number} has been approved."
    //             ],
    //             [
    //                 'user_type' => 'customers',
    //                 'targetable_id' => $order->customer->id ?? null,
    //                 'targetable_type' => 'App\Models\User',
    //                 'token' => $order->customer->fcm_token ?? null,
    //                 'title' => "Order Cancelled",
    //                 'message' => "Your order #{$order->order_number} has been cancelled."
    //             ]
    //         ];
    //         foreach ($notifications as $notify) {
    //             $notification = Notification::create([
    //                 'user_type' => $notify['user_type'] ?? null,
    //                 'title' => $notify['title'] ?? null,
    //                 'description' => $notify['message'] ?? null,
    //             ]);
    //             NotificationTarget::create([
    //                 'notification_id' => $notification->id,
    //                 'targetable_id' => $notify['targetable_id'] ?? null,
    //                 'targetable_type' => $notify['targetable_type'] ?? null,
    //                 'type' => 'order_cancellation',
    //             ]);

    //             if (!empty($notify['token'])) { // null token se bachne ke liye
    //                 NotificationHelper::sendFcmNotification(
    //                     $notify['token'],
    //                     "Order Cancelled",
    //                     $notify['message'],
    //                     [
    //                         'type' => 'order_cancellation',
    //                         'order_id' => (string) $cancelOrder->order_id,
    //                     ]
    //                 );
    //             }
    //         }
    //         return [$notifications, $order];

    //         return response()->json([
    //             'delivery_method' => 'online'
    //         ]);
    //     }

    //     $cancelOrder->status = 'approved';
    //     $cancelOrder->save();

    //     return response()->json([
    //         'delivery_method' => 'approved_direct'
    //     ]);
    // }

    public function updateCancelStatus(Request $request, $id)
{
    $cancelOrder = CancelOrder::with([
        'order.customer',
        'order.items.vendor'
    ])->findOrFail($id);

    $order = $cancelOrder->order;

    // Upload file (for approve case)
    if ($request->hasFile('proof_file_image')) {
        $file = $request->file('proof_file_image');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/cancel_proofs'), $filename);
        $cancelOrder->proof_file_image = 'uploads/cancel_proofs/' . $filename;
    }

    // ✅ UPDATE STATUS HERE
    $cancelOrder->status = $request->status;
    $cancelOrder->save();

    /*
    |-----------------------------------------
    | SEND NOTIFICATIONS
    |-----------------------------------------
    */

    if ($order->delivery_method === 'online') {

        // ================= APPROVED =================
        if ($request->status === 'approved') {

            $order->order_status = 'cancelled';
            $order->save();

            $notifications = [
                [
                    'user_type' => 'vendors',
                    'targetable_id' => $order->items->first()->vendor->id ?? null,
                    'targetable_type' => 'App\Models\Vendor',
                    'token' => $order->items->first()?->vendor?->fcm_token,
                    'title' => "Order Cancellation Approved",
                    'message' => "Your cancellation request for order #{$order->order_number} has been approved."
                ],
                [
                    'user_type' => 'customers',
                    'targetable_id' => $order->customer->id ?? null,
                    'targetable_type' => 'App\Models\User',
                    'token' => $order->customer?->fcm_token,
                    'title' => "Order Cancelled",
                    'message' => "Your order #{$order->order_number} has been cancelled."
                ]
            ];

        }

        // ================= REJECTED =================
        elseif ($request->status === 'rejected') {

            $notifications = [
                [
                    'user_type' => 'vendors',
                    'targetable_id' => $order->items->first()->vendor->id ?? null,
                    'targetable_type' => 'App\Models\Vendor',
                    'token' => $order->items->first()?->vendor?->fcm_token,
                    'title' => "Cancellation Rejected",
                    'message' => "Cancellation request for order #{$order->order_number} has been rejected."
                ],
                [
                    'user_type' => 'customers',
                    'targetable_id' => $order->customer->id ?? null,
                    'targetable_type' => 'App\Models\User',
                    'token' => $order->customer?->fcm_token,
                    'title' => "Cancellation Rejected",
                    'message' => "Your cancellation request for order #{$order->order_number} was rejected."
                ]
            ];
        }

        // 🔁 SEND LOOP (common)
        if (!empty($notifications)) {
            foreach ($notifications as $notify) {

                $notification = Notification::create([
                    'user_type' => $notify['user_type'],
                    'title' => $notify['title'],
                    'description' => $notify['message'],
                ]);

                NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $notify['targetable_id'],
                    'targetable_type' => $notify['targetable_type'],
                    'type' => 'order_cancellation',
                ]);
            
                if (!empty($notify['token'])) {
                    NotificationHelper::sendFcmNotification(
                        $notify['token'],
                        $notify['title'],
                        $notify['message'],
                        [
                            'type' => 'order_cancellation',
                            'order_id' => (string) $cancelOrder->order_id,
                        ]
                    );
                }
            }
        }
    }

    return response()->json([
        'success' => true
    ]);
}

   public function checkDeliveryStatus($id)
{
    $cancelOrder = CancelOrder::with('order')->findOrFail($id);

    if ($cancelOrder->order->delivery_method === 'online') {
        return response()->json([
            'delivery_method' => 'online'
        ]);
    }

    return response()->json([
        'delivery_method' => 'approved_direct'
    ]);
}


    public function pendingCancelOrderCounter()
    {
        $count = CancelOrder::where('status', 'requested')->whereHas('order', function ($query) {
            $query->where('delivery_method', 'online');
        })->count();

        return response()->json(['count' => $count]);
    }


    public function deleteCancelOrder($id)
    {
        $cancelOrder = CancelOrder::findOrFail($id);
        $cancelOrder->delete();

        return redirect()->route('cancel-order.index')
            ->with('success', 'Cancel Order Deleted Successfully');
    }
}
