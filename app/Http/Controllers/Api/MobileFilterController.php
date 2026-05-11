<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\MobileModel;
use App\Models\VendorMobile;
use App\Models\Vendor;
use App\Models\CustomerLastSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MobileListing;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;

class MobileFilterController extends Controller
{
     /**
     * Get all brands
     */
    public function getBrands()
    {
        try {
            $brands = Brand::all();

            return ResponseHelper::success(
                $brands,
                'Brands fetched successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching brands: ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                'Failed to fetch brands',
                'server_error',
                500
            );
        }
    }

    /**
     * Get models by brand_id
     */
    public function getModels($brand_id)
    {
        try {
            $models = MobileModel::where('brand_id', $brand_id)->get();

            if ($models->isEmpty()) {
                return ResponseHelper::error(
                    null,
                    'No Models Found For This Brand',
                    'not_found',
                    200
                );
            }

            return ResponseHelper::success(
                $models,
                'Mobile models fetched successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching models for brand ID ' . $brand_id . ': ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                'Failed to fetch mobile models',
                'server_error',
                500
            );
        }
    }

    /**
     * Get filtered listings by brand, model, storage, ram, etc.
     */
    

// public function getData(Request $request)
// {
//     try {
//         // ---------------------------
//         // REQUIRED FIELDS
//         // ---------------------------
//         if (!$request->filled('brand_id') || !$request->filled('model_id')) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Please enter required details first'
//             ], 400);
//         }

//         // ---------------------------
//         // BASE QUERY
//         // ---------------------------
//         $query = VendorMobile::with('vendor', 'model')
//             ->where('brand_id', $request->brand_id)
//             ->where('model_id', $request->model_id);

//         // OPTIONAL FILTERS
//         if ($request->filled('repair_service')) {
//             $query->whereHas('vendor', function ($q) use ($request) {
//                 $q->where('repair_service', $request->repair_service);
//             });
//         }
//         if ($request->filled('storage'))   $query->where('storage', $request->storage);
//         if ($request->filled('ram'))       $query->where('ram', $request->ram);
//         if ($request->filled('condition')) $query->where('condition', $request->condition);
//         if ($request->filled('color'))     $query->where('color', $request->color);

//         // ---------------------------
//         // PRICE FILTERING USING LIKE
//         // ---------------------------
//         $min = $request->min_price;
//         $max = $request->max_price;

//         if ($min !== null && $min !== '') $min = trim($min);
//         else $min = null;

//         if ($max !== null && $max !== '') $max = trim($max);
//         else $max = null;

//         if ($min && $max) {
//             if ($min > $max) [$min, $max] = [$max, $min]; // swap if needed
//             $query->where(function ($q) use ($min, $max) {
//                 $q->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$min%"])
//                   ->orWhereRaw("CAST(price AS CHAR) LIKE ?", ["%$max%"]);
//             });
//         } elseif ($min) {
//             $query->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$min%"]);
//         } elseif ($max) {
//             $query->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$max%"]);
//         }

//         // ---------------------------
//         // FETCH ALL RESULTS
//         // ---------------------------
//       $listings = $query->get();
//         // ---------------------------
//         // LOCATION LOGIC
//         // ---------------------------
//         $cityMode   = $request->filled('city');
//         $radiusMode = $request->filled('latitude') && $request->filled('longitude');

//         if ($cityMode) {
//             $city = $request->city;
//             $listings = $listings->filter(fn($item) => ($item->location ?? null) === $city)->values();
//         } elseif ($radiusMode) {
//             $lat    = $request->latitude;
//             $lng    = $request->longitude;
//             $radius = $request->radius ?? 50;

//             $listings = $listings->filter(function ($item) use ($lat, $lng, $radius) {
//                 if (!$item->vendor?->latitude || !$item->vendor?->longitude) return false;

//                 $theta = $lng - $item->vendor->longitude;
//                 $dist = sin(deg2rad($lat)) * sin(deg2rad($item->vendor->latitude)) +
//                         cos(deg2rad($lat)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
//                 $dist = acos($dist);
//                 $dist = rad2deg($dist);
//                 $miles = $dist * 60 * 1.1515;
//                 $km = $miles * 1.609344;

//                 return $km <= $radius;
//             })->values();
//         }

// 		 $listings = $query->get();
//         // ---------------------------
//         // RETURN RESULTS
//         // ---------------------------
//         return response()->json([
//             'status' => 'success',
//             'data'   => $listings
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

    // public function getData(Request $request)
    // {
    //     try {
    //         // ---------------------------
    //         // REQUIRED FIELDS
    //         // ---------------------------
    //         // if (!$request->filled('brand_id') || !$request->filled('model_id')) {
    //         //     return response()->json([
    //         //         'status' => 'error',
    //         //         'message' => 'Please enter required details first'
    //         //     ], 400);
    //         // }

    //         // ---------------------------
    //         // BASE QUERY
    //         // ---------------------------
    //         $query = VendorMobile::with('vendor', 'model')
    //             ->when($request->brand_id, function ($q) use ($request) {
    //                 $q->where('brand_id', $request->brand_id);
    //             })
    //             ->when($request->model_id, function ($q) use ($request) {
    //                 $q->where('model_id', $request->model_id);
    //             })
    //             ->where('status', 0);

    //         // ---------------------------
    //         // OPTIONAL FILTERS
    //         // ---------------------------
    //         if ($request->filled('repair_service')) {
    //             $query->whereHas('vendor', function ($q) use ($request) {
    //                 $q->where('repair_service', $request->repair_service);
    //             });
    //         }
    //         if ($request->filled('storage'))   $query->where('storage', $request->storage);
    //         if ($request->filled('ram'))       $query->where('ram', $request->ram);
    //         if ($request->filled('condition')) $query->where('condition', $request->condition);
    //         if ($request->filled('color'))     $query->where('color', $request->color);

    //         // ---------------------------
    //         // PRICE FILTER (LIKE)
    //         // ---------------------------
    //         // ---------------------------
    //         // PRICE FILTER (BETWEEN)
    //         // ---------------------------
    //         if ($request->filled('min_price') && $request->filled('max_price')) {

    //             $min = (int) $request->min_price;
    //             $max = (int) $request->max_price;

    //             if ($min > $max) {
    //                 [$min, $max] = [$max, $min];
    //             }

    //             $query->whereBetween('price', [$min, $max]);

    //         } elseif ($request->filled('min_price')) {

    //             $query->where('price', '>=', (int) $request->min_price);

    //         } elseif ($request->filled('max_price')) {

    //             $query->where('price', '<=', (int) $request->max_price);
    //         }


    //         // ---------------------------
    //         // FETCH RESULTS
    //         // ---------------------------
    //         $listings = $query->get();

    //         // ---------------------------
    //         // LOCATION LOGIC
    //         // ---------------------------
    //         $radius = null;
    //         $hasCity   = $request->filled('city');
    //         $hasLatLng = $request->filled('latitude') && $request->filled('longitude');
    //         $hasRadius = $request->filled('radius');

    //         $latReq = $request->latitude;
    //         $lngReq = $request->longitude;

    //         // CASE 1: City filter takes priority
    //         if ($hasCity) {
    //             $city = strtolower($request->city);
    //             $listings = $listings->filter(function ($item) use ($city) {
    //                 $vendorLocation = $item->vendor->location ?? '';
    //                 // case-insensitive search
    //                 return stripos($vendorLocation, $city) !== false;
    //             })->values();
    //         }
    //         // CASE 2: Latitude/longitude with optional radius
    //         elseif ($hasLatLng) {
    //             $radius = $hasRadius ? $request->radius : 50; // default 50 km
    //             $listings = $listings->filter(function ($item) use ($latReq, $lngReq, $radius) {
    //                 if (!$item->vendor?->latitude || !$item->vendor?->longitude) return false;

    //                 $theta = $lngReq - $item->vendor->longitude;
    //                 $dist = sin(deg2rad($latReq)) * sin(deg2rad($item->vendor->latitude)) +
    //                         cos(deg2rad($latReq)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
    //                 $dist = acos($dist);
    //                 $dist = rad2deg($dist);
    //                 $km   = $dist * 60 * 1.1515 * 1.609344;

    //                 return $km <= $radius;
    //             })->values();
    //         }

    //         // ---------------------------
    //         // FORMAT RESPONSE
    //         // ---------------------------
    //         $formatted = $listings->map(function ($item) use ($radius, $latReq, $lngReq) {
    //             $images = is_string($item->image) && is_array(json_decode($item->image, true))
    //             ? json_decode($item->image, true)
    //             : ($item->image ? [$item->image] : []);

    //             $distance = null;
    //             if ($radius !== null && $item->vendor?->latitude && $item->vendor?->longitude) {
    //                 $theta = $lngReq - $item->vendor->longitude;
    //                 $dist = sin(deg2rad($latReq)) * sin(deg2rad($item->vendor->latitude)) +
    //                         cos(deg2rad($latReq)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
    //                 $dist = acos($dist);
    //                 $dist = rad2deg($dist);
    //                 $distance = round($dist * 60 * 1.1515 * 1.609344, 2);
    //             }

    //             return [
    //                 'id' => $item->id,
    //                 "vendor"    => $item->vendor->name ?? null,
    //                 'model'     => $item->model->name ?? null,
    //                 'price'     => $item->price,
    //                 'distance'  => round($distance) . ' km',
    //                 "repair_service" => $item->vendor->	repair_service ?? null,
    //                 'image'     => array_map(fn ($path) => asset($path), $images),
    //             ];
    //         });

    //         return response()->json([
    //             'status' => 'success',
    //             'count'  => $formatted->count(),
    //             'data'   => $formatted
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getData(Request $request)
    {
        try {

            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->filled('radius') ? $request->radius : 50;

            $query = VendorMobile::query()
                ->with(['vendor','model','brand'])
                ->join('vendors','vendor_mobiles.vendor_id','=','vendors.id')
                ->select('vendor_mobiles.*');

            /*
            |--------------------------------------------------
            | Distance Calculation (Haversine)
            |--------------------------------------------------
            */

            if ($request->filled('latitude') && $request->filled('longitude')) {

                $query->selectRaw("
                    (
                        6371 * acos(
                            least(1,
                                greatest(-1,
                                    cos(radians(?)) *
                                    cos(radians(vendor_mobiles.latitude)) *
                                    cos(radians(vendor_mobiles.longitude) - radians(?)) +
                                    sin(radians(?)) *
                                    sin(radians(vendor_mobiles.latitude))
                                )
                            )
                        )
                    ) AS distance
                ", [$lat, $lng, $lat])
                ->orderBy('distance','asc');
                // Radius sirf tab lage jab city select na ho
                if (!$request->filled('city')) {
                    $query->havingRaw('distance <= ?', [$radius]);
                }

            }

            /*
            |--------------------------------------------------
            | Basic Filters
            |--------------------------------------------------
            */

            if($request->filled('brand_id')){
                $query->where('vendor_mobiles.brand_id',$request->brand_id);
            }

            if($request->filled('model_id')){
                $query->where('vendor_mobiles.model_id',$request->model_id);
            }

            if($request->filled('vendor_id')){
                $query->where('vendor_mobiles.vendor_id',$request->vendor_id);
            }

            $query->where('vendor_mobiles.status',0);

            /*
            |--------------------------------------------------
            | Vendor Filters
            |--------------------------------------------------
            */

            if($request->filled('repair_service')){
                $query->where('vendors.repair_service',$request->repair_service);
            }

            /*
            |--------------------------------------------------
            | Mobile Filters
            |--------------------------------------------------
            */

            if($request->filled('storage')){
                $query->where('vendor_mobiles.storage',$request->storage);
            }

            if($request->filled('ram')){
                $query->where('vendor_mobiles.ram',$request->ram);
            }

            if($request->filled('condition')){
                $query->where('vendor_mobiles.condition',$request->condition);
            }

            if($request->filled('color')){
                $query->where('vendor_mobiles.color',$request->color);
            }

            /*
            |--------------------------------------------------
            | Price Filter
            |--------------------------------------------------
            */

            if($request->filled('min_price') && $request->filled('max_price')){

                $min = (int)$request->min_price;
                $max = (int)$request->max_price;

                if($min > $max){
                    [$min,$max] = [$max,$min];
                }

                $query->whereBetween('vendor_mobiles.price',[$min,$max]);
            }
            elseif($request->filled('min_price')){
                $query->where('vendor_mobiles.price','>=',$request->min_price);
            }
            elseif($request->filled('max_price')){
                $query->where('vendor_mobiles.price','<=',$request->max_price);
            }

            /*
            |--------------------------------------------------
            | City Filter (Priority)
            |--------------------------------------------------
            */

            if($request->filled('city')){

                $city = strtolower($request->city);
                $query->where('vendor_mobiles.city',$request->city);
                // $query->where('vendor_mobiles.city','Lahore');
                
                // $query->whereRaw("LOWER(vendor_mobiles.city) LIKE ?",['%'.$city.'%']);
            }

            /*
            |--------------------------------------------------
            | Radius Filter
            |--------------------------------------------------
            */

            // if($request->filled('latitude') && $request->filled('longitude')){

            //     $query->having('distance','<=',$radius)
            //         ->orderBy('distance','asc');
            // }

            /*
            |--------------------------------------------------
            | Fetch Data
            |--------------------------------------------------
            */

            $listings = $query->where('stock', '>', 0)->get();

            /*
            |--------------------------------------------------
            | Format Response
            |--------------------------------------------------
            */

            $formatted = $listings->map(function($item){

                $images = is_string($item->image) && is_array(json_decode($item->image,true))
                    ? json_decode($item->image,true)
                    : ($item->image ? [$item->image] : []);

                return [

                    'id' => $item->id,

                    'vendor' => $item->vendor->name ?? null,

                    'model' => $item->model->name ?? null,
                    'brand' => $item->brand->name ?? null,

                    'price' => $item->price,

                    'distance' => isset($item->distance)
                        ? round($item->distance, 1)." km"
                        : null,

                    'repair_service' => $item->vendor->repair_service ?? null,

                    'image' => array_map(fn($path)=>asset($path),$images)

                ];
            });

            CustomerLastSearch::updateOrCreate(
            [
                'customer_id' => Auth::id()
            ],
            [
                'filters' => $request->all(), // 👈 all filters
                'data' => $formatted->toArray() // 👈 optional (API response)
            ]
        );

            return response()->json([
                'status'=>'success',
                'count'=>$formatted->count(),
                'data'=>$formatted
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'=>'error',
                'message'=>$e->getMessage()
            ],500);

        }
    }

       public function getLastSearch()
{
    try {
        $customerId = Auth::id();

        if (!$customerId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $lastSearch = CustomerLastSearch::where('customer_id', $customerId)
                        ->latest() // get the latest search
                        ->first();
        $filters = $lastSearch->filters;

        /*
        |------------------------------------------
        | Add model name
        |------------------------------------------
        */
        if (!empty($filters['model_id'])) {

            $model = MobileModel::find($filters['model_id']); // 👈 your Model table

            if ($model) {
                $filters['model_name'] = $model->name;
            }
        }

        if (!empty($filters['brand_id'])) {

            $brand = Brand::find($filters['brand_id']); // 👈 your Model table

            if ($brand) {
                $filters['brand_name'] = $brand->name;
            }
        }

        $vendor_name = Vendor::where('id', $filters['vendor_id'] ?? null)->value('name');
        $filters['vendor_name'] = $vendor_name;


        return response()->json([
            'status' => 'success',
            'message' => 'Last search fetched successfully',
            'data' => $filters
        ]);
    }  catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


public function getMinMaxPrice()
{
    try {
        $minPrice = VendorMobile::min('price');
        $maxPrice = VendorMobile::max('price');

        return response()->json([
            'status' => 'success',
            'message' => 'Price range fetched successfully',
            'data' => [
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch price range',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function getCities()
{
    try {

        $cities = VendorMobile::where('status', 0)
            ->whereNotNull('city')
            ->where('stock', '>', 0)
            ->select('city')
            ->distinct()
            ->pluck('city');

        $formatted = $cities->map(fn($city) => [
            'label' => $city,
            'value' => $city
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $formatted->values()
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);

    }
}


}
