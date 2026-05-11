<?php

namespace App\Services\Api;

use Carbon\Carbon;
use App\Traits\SendsBulkEmails;
use App\Models\MobileListing;
use App\Models\Vendor;
use App\Models\Brand;
use App\Models\MobileModel;
use App\Helpers\NotificationHelper;
use App\Models\Notification;
use App\Models\NotificationTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Mail\NotifyNearByVendorsOfListedMobile;
use App\Repositories\Api\MobileListingRepository;

class MobileListingService
{

    protected $mobileListingRepo;

    public function __construct(MobileListingRepository $mobileListingRepo)
    {
        $this->mobileListingRepo = $mobileListingRepo;
    }

    

public function createCustomerListing($request)
{
    $customerId = Auth::id();
    $customer = Auth::user();
    if (!$customerId) {
        throw new \Exception('User not authenticated');
    }
    // 🔍 Pehle check karo ke ye mobile pehle se listed to nahi
    $alreadyExists = MobileListing::where('customer_id', $customerId)
        ->where('brand', $request->brand)
        ->where('model', $request->model)
        ->exists();

    if ($alreadyExists) {
            // Instead of throwing exception, bas error message return kar do
            return [
                'error' => true,
                'message' => 'You have already listed this mobile model.'
            ];
        }

        // ✅ Handle media upload
        $mediaPaths = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $file) {
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $file->move(public_path('admin/assets/images/users/'), $filename);
                $mediaPaths[] = 'public/admin/assets/images/users/' . $filename;
            }
        }

        $videos = [];
            if ($request->hasFile('video')) {
                foreach ($request->file('video') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('admin/assets/videos/'), $filename);
                    $videos[] = 'public/admin/assets/videos/' . $filename;
                }
            }

        $data = [
            'brand'    => $request->brand,
            'model'    => $request->model,
            'location'    => $request->location,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'storage'     => $request->storage,
            'ram'         => $request->ram,
            'price'       => $request->price,
            'condition'   => $request->condition,
            'about'       => $request->about,
            'customer_id' => $customerId,
            'image'       => json_encode($mediaPaths),
            'video'       => json_encode($videos),
        ];
        $listing = $this->mobileListingRepo->create($data);
        $data['id'] = $listing->id;
        $data['image'] = array_map(fn($path) => asset($path), $mediaPaths);
        $data['video'] = array_map(fn($path) => asset($path), $videos);
            $notification = Notification::create([
                    'user_type' => 'customers',
                    'title' => "Requested New Mobile Listing",
                    'description' => "Your mobile listing is under review. We'll notify you once approved.",
                ]);
            NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $customerId,
                    'targetable_type' => 'App\Models\User',
                    'type' => 'requested_customer_mobile_listed',
                ]);
            if (!empty($customer->fcm_token)) {
                    NotificationHelper::sendFcmNotification(
                        $customer->fcm_token,
                        "Requested New Mobile Listing",
                        "Your mobile listing is under review. We'll notify you once approved.",
                        [
                            'type' => 'requested_customer_mobile_listed',
                            'order_id' => (string) $listing->id,
                        ]
                    );
            }
        return $data; 
}


public function previewCustomerListing($id)
{
    $listing = $this->mobileListingRepo->findWithRelations($id);

    $images = json_decode($listing->image, true) ?? [];

    return [
        'id'        => $listing->id,
        'brand'     => $listing->brand ? $listing->brand->name : null,
        'model'     => $listing->model ? $listing->model->name : null,
        'storage'   => $listing->storage,
        'ram'       => $listing->ram,
        'price'     => $listing->price,
        'condition' => $listing->condition,
        'about'     => $listing->about,
        'customer_id' => $listing->customer_id,
        'image'     => array_map(fn($path) => asset($path), $images),
    ];
}

public function getcustomermobileListing()
{
    $customer = Auth::id();
        // return $customer;

        $listings = MobileListing::with(['customer'])
            ->where('customer_id', $customer)
            ->get()
            ->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'model' => $listing->model ? $listing->model : null,
                    'brand' => $listing->brand ? $listing->brand : null,
                    'customer' => $listing->customer ? $listing->customer->name : null,
                    'price' => $listing->price,
                    'image' => $listing->image 
                        ? asset(is_array(json_decode($listing->image, true)) 
                            ? json_decode($listing->image, true)[0]  // return only first image
                            : json_decode($listing->image, true)) 
                        : null,
                    'status' => $listing->status,
                    'is_sold' => $listing->is_sold,
                ];
            });
        $data = $listings->count() === 1 ? $listings->first() : $listings;
        return $data;

}

public function getcustomernearbyListings($vendorLat, $vendorLng, $radius = 50)
{
    return MobileListing::with('customer')
        ->select('*', DB::raw("
            (6371 * acos(
                cos(radians($vendorLat)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians($vendorLng)) +
                sin(radians($vendorLat)) * sin(radians(latitude))
            )) AS distance
        "))
        ->having('distance', '<=', $radius) // filter within radius (default 30 km)
        ->orderBy('distance', 'asc')
        ->where('is_sold', 0)
        ->where('status', 0)
        ->get()
        ->map(function ($listing) {
           // ✅ Images
            $images = json_decode($listing->image, true) ?? [];
            $imageUrls = array_map(fn ($img) => asset($img), $images);

            // ✅ Videos
            $videos = json_decode($listing->video, true) ?? [];
            $videoUrls = array_map(fn ($vid) => asset($vid), $videos);

            return [
                'customer_id' => $listing->customer->id ?? null,
                'customer_name' => $listing->customer->name ?? null,
                'customer_phone' => $listing->customer->phone ?? null,
                'customer_image' => $listing->customer->image ?? null,
                'id' => $listing->id ?? null,
                'brand' => $listing->brand ?? null,
                'model' => $listing->model ?? null,
                'storage' => $listing->storage ?? null,
                'ram' => $listing->ram ?? null,
                'condition' => $listing->condition ?? null,
                'customer' => $listing->customer ? $listing->customer->name : null,
                'price' => $listing->price ?? null,
                'about' => $listing->about ?? null,
                'image' => $imageUrls ?? [],
                'video' => $videoUrls ?? [],
                'distance' => round($listing->distance, 1) . ' km' ?? null,
            ];
        });
}


   public function getCustomerDeviceDetail($id)
    {
        return MobileListing::with([
            'brand:id,name',
            'model:id,name'
        ])
        ->where('id', $id)
        ->select('id','brand_id','model_id','storage','price','condition','ram','image')
        ->first()
        ->makeHidden(['brand_id', 'model_id']) // hide ids
        ->append(['brand_name','model_name']); // custom attributes
    }

}
