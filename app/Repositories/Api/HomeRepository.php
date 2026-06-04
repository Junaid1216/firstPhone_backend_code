<?php

namespace App\Repositories\Api;

use App\Models\OrderItem;
use App\Models\VendorMobile;
use App\Models\CustomerLastSearch;
use Illuminate\Http\Request;
use App\Models\MobileListing;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Api\Interfaces\HomeRepositoryInterface;

class HomeRepository implements HomeRepositoryInterface
{
    // public function getNearbyListings($request)
    // {
    //     $customerLat = $request->query('latitude');
    //     $customerLng = $request->query('longitude');

    //     if (!$customerLat || !$customerLng) {
    //         throw new \Exception('Latitude and Longitude are required to fetch nearby listings');
    //     }

    //     $radius = $request->query('radius', 50);
    //     $search = $request->query('search');
    //     $startDate = $request->query('start_date');
    //     $endDate = $request->query('end_date');

    //     $query = VendorMobile::with(['brand', 'model', 'vendor'])
    //         // ->where('status', 0)
    //         ->join('vendors', 'vendor_mobiles.vendor_id', '=', 'vendors.id')
    //         ->select(
    //             'vendor_mobiles.id',
    //             'vendor_mobiles.vendor_id',
    //             'vendor_mobiles.brand_id',
    //             'vendor_mobiles.model_id',
    //             'vendor_mobiles.price',
    //             'vendor_mobiles.image',
    //             'vendor_mobiles.stock',
    //             'vendor_mobiles.location',
    //             'vendors.latitude',
    //             'vendors.longitude',
    //             'vendors.repair_service',
    //         )
    //         ->selectRaw("
    //             (6371 * acos(
    //                 cos(radians(?)) * cos(radians(vendors.latitude)) *
    //                 cos(radians(vendors.longitude) - radians(?)) +
    //                 sin(radians(?)) * sin(radians(vendors.latitude))
    //             )) AS distance
    //         ", [$customerLat, $customerLng, $customerLat])
    //         ->having('distance', '<=', $radius)
    //         ->orderBy('distance', 'asc')
    //         ->where('vendor_mobiles.status', 0)
    //         ->where('vendor_mobiles.stock', '>', 0);

    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('vendor_mobiles.price', 'LIKE', "%{$search}%")
    //             ->orWhere('vendor_mobiles.location', 'LIKE', "%{$search}%")
    //             ->orWhere('vendor_mobiles.storage', 'LIKE', "%{$search}%")
    //             ->orWhere('vendor_mobiles.ram', 'LIKE', "%{$search}%")
    //             ->orWhereHas('model', fn($m) => $m->where('name', 'LIKE', "%{$search}%"));
    //         });
    //     }

    //     if (!empty($startDate) && !empty($endDate)) {
    //         $query->whereBetween('vendor_mobiles.created_at', [$startDate, $endDate]);
    //     }

    //     return $query->get()->map(function ($listing) {
    //         $images = json_decode($listing->image, true) ?? [];

    //         return [
    //             'id'             => $listing->id,
    //             // 'vendor'         => $listing->vendor?->name,
    //             'vendor_id'     => $listing->vendor?->id,
    //             'vendor_name'   => $listing->vendor?->name,
    //             'vendor_image'  => $listing->vendor?->image,
    //             'vendor_phone'  => $listing->vendor?->phone,
    //             'brand'         => $listing->brand?->name,
    //             'model'          => $listing->model?->name,
    //             'price'          => $listing->price,
    //             'distance'       => round($listing->distance, 1) . ' km',
    //             'repair_service' => $listing->vendor?->repair_service,
    //             'image'          => isset($images[0]) ? asset($images[0]) : null,
    //         ];
    //     });
    // }

     public function getNearbyListings($request)
    {         
        // return CustomerLastSearch::where('customer_id',$request->query('customer_id'))->get();  
        $customerLat = $request->query('latitude');
        $customerLng = $request->query('longitude'); 
        if ($request->query('delete_filter') == true) {
        // return CustomerLastSearch::where('customer_id',$request->query('customer_id'))->get();  

            CustomerLastSearch::where('customer_id',$request->query('customer_id'))->delete();
        }
        if (!$customerLat || !$customerLng) {
            throw new \Exception('Latitude and Longitude are required to fetch nearby listings');
        }

        // Default radius 50 km
        $radius = 50;

        $query = VendorMobile::with(['brand', 'model', 'vendor'])
            ->join('vendors', 'vendor_mobiles.vendor_id', '=', 'vendors.id')
            ->where('vendor_mobiles.stock', '>', 0)
            ->where('vendor_mobiles.status', '==', 0)
            ->where('vendors.status', '!=', 'deactivated')
            ->select(
                'vendor_mobiles.id',
                'vendor_mobiles.vendor_id',
                'vendor_mobiles.brand_id',
                'vendor_mobiles.model_id',
                'vendor_mobiles.price',
                'vendor_mobiles.image',
                'vendor_mobiles.location',
                'vendors.latitude',
                'vendors.longitude',
                'vendors.repair_service'
            );
            if($customerLat && $customerLng){
                $query->selectRaw("
                    (
                        6371 * acos(
                            least(1,
                                greatest(-1,
                                    cos(radians(?)) *
                                    cos(radians(vendors.latitude)) *
                                    cos(radians(vendors.longitude) - radians(?)) +
                                    sin(radians(?)) *
                                    sin(radians(vendors.latitude))
                                )
                            )
                        )
                    ) AS distance
                ", [$customerLat, $customerLng, $customerLat])
                ->havingRaw('distance <= ?', [$radius])
                ->orderBy('distance','asc')
                ->get();
            }

        return $query->get()->map(function ($listing) {
            $images = json_decode($listing->image, true) ?? [];

            return [
                'id'             => $listing->id,
                'vendor_id'      => $listing->vendor?->id,
                'vendor_name'    => $listing->vendor?->name,
                'vendor_image'   => $listing->vendor?->image,
                'vendor_phone'   => $listing->vendor?->phone,
                'brand'          => $listing->brand?->name,
                'model'          => $listing->model?->name,
                'price'          => $listing->price,
                'distance'       => round($listing->distance, 1) . ' km',
                'repair_service' => $listing->vendor?->repair_service,
                'image'          => $images[0] ?? null,
            ];
        });
    }

    public function getTopSellingListings($request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // User location
        $customerLat = $request->query('latitude');
        $customerLng = $request->query('longitude');

        // Radius default 50
        $radius = $request->query('radius', 50);

        $query = OrderItem::with(['product.brand', 'product.model', 'product.vendor', 'order'])
            ->whereHas('order', fn($q) => $q->where('order_status', 'delivered'))
             ->whereHas('product', function ($q) {
                $q->where('stock', '>', 0)
                ->where('status', 0)
                ->whereHas('vendor', function ($vendor) {
                    $vendor->where('status', '!=', 'deactivated');
                });
            });

        // Search filter
        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('price', 'LIKE', "%{$search}%")
                ->orWhere('location', 'LIKE', "%{$search}%")
                ->orWhere('storage', 'LIKE', "%{$search}%")
                ->orWhere('ram', 'LIKE', "%{$search}%")
                ->orWhereHas('model', fn($m) => $m->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Date filter
        if (!empty($startDate) && !empty($endDate)) {
            $query->whereHas('product', fn($q) =>
                $q->whereBetween('created_at', [$startDate, $endDate])
            );
        }

        $topSelling = $query->get()
            ->groupBy('product_id')
            ->map(function ($items) use ($customerLat, $customerLng, $radius) {
                $product = $items->first()->product;

                if (!$product) return null;

                $distance = null;

                if ($customerLat !== null && $customerLng !== null && $product->vendor) {

                    $vLat = $product->vendor->latitude;
                    $vLng = $product->vendor->longitude;

                    if ($vLat !== null && $vLng !== null) {

                        $customerLat = (float) $customerLat;
                        $customerLng = (float) $customerLng;
                        $vLat = (float) $vLat;
                        $vLng = (float) $vLng;

                        $distance = 6371 * acos(
                            cos(deg2rad($customerLat)) *
                            cos(deg2rad($vLat)) *
                            cos(deg2rad($vLng) - deg2rad($customerLng)) +
                            sin(deg2rad($customerLat)) *
                            sin(deg2rad($vLat))
                        );

                        $distance = round($distance, 1); // KM
                    }
                }

                // ❗ If radius check is required but no location = skip
                if ($customerLat && $customerLng && $distance !== null) {
                    if ($distance > $radius) {
                        return null; // skip items outside radius
                    }
                }

                // Images
                $images = json_decode($product->image, true) ?? [];

                return [
                    'id'            => $product->id,
                    'vendor_id'     => $product->vendor?->id,
                    'vendor_name'   => $product->vendor?->name,
                    'vendor_image'  => $product->vendor?->image,
                    'vendor_phone'  => $product->vendor?->phone,
                    'brand'         => $product->brand?->name,
                    'model'         => $product->model?->name,
                    'price'         => $product->price,
                    'image'         => isset($images[0]) ? $images[0] : null,
                    'total_sales'   => $items->count(),
                    'distance'      => $distance ? $distance . ' km' : null,
                    'repair_service'=> $product->vendor?->repair_service, 
                ];

            })
            ->filter()
            ->sortByDesc('total_sales')
            ->take(6)
            ->values();

        return $topSelling;
    }


   public function getDeviceDetails($id)
    {
        $orderItem = OrderItem::findOrFail($id);

        // Try getting product from vendor_mobiles
        $listing = VendorMobile::with(['brand','model'])
        ->find($orderItem->product_id);


         // If product deleted → fallback to order_items
    if (!$listing) {

            $images = $orderItem->image
            ? json_decode($orderItem->image, true)
            : null;

        $videos = $orderItem->video
            ? json_decode($orderItem->video, true)
            : null;

        return [
            'status' => 'success',

            'specifications' => [[
                'product_id' => $orderItem->product_id,
                'brand'      => $orderItem->brand_name,
                'model'      => $orderItem->model_name,
                'storage'    => $orderItem->storage,
                'price'      => $orderItem->price,
                'condition'  => $orderItem->condition,
                'color'      => $orderItem->color,
                'ram'        => $orderItem->ram,
                'processor'  => $orderItem->processor,
                'display'    => $orderItem->display,
                'charging'   => $orderItem->charging,
                'refresh_rate' => $orderItem->refresh_rate,
                'main_camera'  => $orderItem->main_camera,
                'ultra_camera' => $orderItem->ultra_camera,
                'telephoto_camera' => $orderItem->telephoto_camera,
                'front_camera' => $orderItem->front_camera,
                'build'        => $orderItem->build,
                'wireless'     => $orderItem->wireless,
                'pta_approved' => $orderItem->pta_approved == 0 ? 'Approved' : 'Not Approved',
                'stock'        => $orderItem->stock,
            ]],

            'other_features' => [[
                'ai_features'    => $orderItem->ai_features,
                'battery_health' => $orderItem->battery_health,
                'os_version'     => $orderItem->os_version,
            ]],

            'warranty_details' => [[
                'warranty_start' => $orderItem->warranty_start,
                'warranty_end'   => $orderItem->warranty_end,
            ]],

            'description' => [$orderItem->about],

            'images' => $images
                ? array_map(fn($p) => asset($p), $images)
                : null,

            'videos' => $videos
                ? array_map(fn($p) => asset($p), $videos)
                : null,
        ];
    }

       // NORMAL PRODUCT FLOW
    $images = empty($listing->image)
        ? null
        : (is_array(json_decode($listing->image, true))
            ? json_decode($listing->image, true)
            : [$listing->image]);

    $videos = empty($listing->video)
        ? null
        : (is_array(json_decode($listing->video, true))
            ? json_decode($listing->video, true)
            : [$listing->video]);

    return [
        'status' => 'success',

        'specifications' => [[
            'product_id' => $listing->id,
            'brand_id'   => $listing->brand->id ?? null,
            'brand'      => $listing->brand->name ?? null,
            'model_id'   => $listing->model->id ?? null,
            'model'      => $listing->model->name ?? null,
            'storage'    => $listing->storage,
            'price'      => $listing->price,
            'condition'  => $listing->condition,
            'color'      => $listing->color,
            'ram'        => $listing->ram,
            'processor'  => $listing->processor,
            'display'    => $listing->display,
            'charging'   => $listing->charging,
            'refresh_rate' => $listing->refresh_rate,
            'main_camera'  => $listing->main_camera,
            'ultra_camera' => $listing->ultra_camera,
            'telephoto_camera' => $listing->telephoto_camera,
            'front_camera' => $listing->front_camera,
            'build'        => $listing->build,
            'wireless'     => $listing->wireless,
            'pta_approved' => $listing->pta_approved == 0 ? 'Approved' : 'Not Approved',
            'stock'        => $listing->stock,
        ]],

        'other_features' => [[
            'ai_features'    => $listing->ai_features,
            'battery_health' => $listing->battery_health,
            'os_version'     => $listing->os_version,
        ]],

        'warranty_details' => [[
            'warranty_start' => $listing->warranty_start,
            'warranty_end'   => $listing->warranty_end,
        ]],

        'description' => [$listing->about],

        'images' => $images
            ? array_map(fn($p) => asset($p), $images)
            : null,

        'videos' => $videos
            ? array_map(fn($p) => asset($p), $videos)
            : null,
    ];
}

 public function getvendorDeviceDetails($id)
    {
        $listing = VendorMobile::with(['brand', 'model'])
            ->where('id', $id)
            ->firstOrFail();

       if (empty($listing->image)) {

        // If NO video uploaded → return null
        $images = null;

    } else {

        // If video exists → decode normally
        $images = is_string($listing->image) && is_array(json_decode($listing->image, true))
            ? json_decode($listing->image, true)
            : [$listing->image];
    }

       if (empty($listing->video)) {

        // If NO video uploaded → return null
        $videos = null;

    } else {

        // If video exists → decode normally
        $videos = is_string($listing->video) && is_array(json_decode($listing->video, true))
            ? json_decode($listing->video, true)
            : [$listing->video];
    }

        return [
            'status' => 'success',

            // Specifications
            'specifications' => [[
                'product_id' => $listing->id,
                'brand_id' => $listing->brand->id,
                'brand'            => $listing->brand ? $listing->brand->name : null,
                'model_id' => $listing->model->id,
                'model'            => $listing->model ? $listing->model->name : null,
                'storage'          => $listing->storage,
                'price'            => $listing->price,
                'condition'        => $listing->condition,
                'color'            => $listing->color,
                'ram'              => $listing->ram,
                'processor'        => $listing->processor,
                'display'          => $listing->display,
                'charging'         => $listing->charging,
                'refresh_rate'     => $listing->refresh_rate,
                'main_camera'      => $listing->main_camera,
                'ultra_camera'     => $listing->ultra_camera,
                'telephoto_camera' => $listing->telephoto_camera,
                'front_camera'     => $listing->front_camera,
                'build'            => $listing->build,
                'wireless'         => $listing->wireless,
                'pta_approved'     => $listing->pta_approved == 0 ? 'Approved' : 'Not Approved',
                'stock'            => $listing->stock,
            ]],

            // Other features
            'other_features' => [[
                'ai_features'    => $listing->ai_features,
                'battery_health' => $listing->battery_health,
                'os_version'     => $listing->os_version,
            ]],

            // Warranty details
            'warranty_details' => [[
                'warranty_start' => $listing->warranty_start,
                'warranty_end'   => $listing->warranty_end,
            ]],

            // Description
            'description' => [$listing->about],

            // Images
             'images' => $images
            ? array_map(fn($p) => asset($p), $images)
            : null,

            // Videos
             'videos' => $videos
            ? array_map(fn($p) => asset($p), $videos)
            : null,
        ];
    }
}
    
