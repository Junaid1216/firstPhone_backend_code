<?php

namespace App\Services\Api;

use Carbon\Carbon;
use App\Traits\SendsBulkEmails;
use Illuminate\Support\Arr;
use App\Models\VendorMobile;
use App\Models\Brand;
use App\Models\MobileModel;
use App\Models\MobileListing;
use App\Models\MobileRequest;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionProducts;
use Illuminate\Auth\Access\AuthorizationException;
use App\Repositories\Api\VendorMobileListingRepository;
use App\Mail\NotifyNearByCustomersOfRequestedMobile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VendorMobileListingService
{
    use SendsBulkEmails;

    protected $vendormobileListingRepo;

    public function __construct(VendorMobileListingRepository $vendormobileListingRepo)
    {
        $this->vendormobileListingRepo = $vendormobileListingRepo;
    }

    //  public function createListing($request)
    // {
    //     $vendorId = Auth::id();
    //     $activePlan = VendorSubscription::with('plan')->where('vendor_id', $vendorId)->where('is_active', true)->first();
    //     // if (VendorSubscriptionProducts::where('vendor_subscription_id', $activePlan->id)->count() >= $activePlan->product_limit) {
    //     //     throw new AuthorizationException('You have reached the maximum number of listings allowed for your subscription plan');
    //     // }
    //     // ✅ Handle media upload
    //     $mediaPaths = [];
    //     if ($request->hasFile('image')) {
    //         foreach ($request->file('image') as $file) {
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = time() . '_' . uniqid() . '.' . $extension;
    //             $file->move(public_path('admin/assets/images/users/'), $filename);
    //             $mediaPaths[] = 'public/admin/assets/images/users/' . $filename;
    //         }
    //     }
    //     // ✅ Handle video upload
    //     $videoPaths = [];
    //     if ($request->hasFile('video')) {
    //         foreach ($request->file('video') as $file) {
    //             $extension = $file->getClientOriginalExtension();
    //             $filename = time() . '_' . uniqid() . '.' . $extension;
    //             $file->move(public_path('admin/assets/videos/'), $filename);
    //             $videoPaths[] = 'public/admin/assets/videos/' . $filename;
    //         }
    //     }

    //     $data = [
    //         'brand_id' => $request->brand_id,
    //         'model_id' => $request->model_id,
    //         'storage' => $request->storage,
    //         'ram' => $request->ram,
    //         'color' => $request->color,
    //         'price' => $request->price,
    //         'condition' => $request->condition,
    //         'about' => $request->about,
    //         'processor' => $request->processor,
    //         'display' => $request->display,
    //         'charging' => $request->charging,
    //         'refresh_rate' => $request->refresh_rate,
    //         'main_camera' => $request->main_camera,
    //         'ultra_camera' => $request->ultra_camera,
    //         'telephoto_camera' => $request->telephoto_camera,
    //         'front_camera' => $request->front_camera,
    //         'build' => $request->build,
    //         'wireless' => $request->wireless,
    //         'stock' => $request->stock,
    //         'ai_features' => $request->ai_features,
    //         'battery_health' => $request->battery_health,
    //         'os_version' => $request->os_version,
    //         'warranty_start' => $request->warranty_start,
    //         'warranty_end' => $request->warranty_end,
    //         'pta_approved' => $request->pta_approved,
    //         'location' => $request->location,
    //         'latitude' => $request->latitude,
    //         'longitude' => $request->longitude,
    //         'vendor_id' => $vendorId,
    //         'image' => json_encode($mediaPaths),
    //         'video' => json_encode($videoPaths),
    //     ];

    //     $data['city'] = $this->updateCitiesFromLocation($request->location);

    //     $listing = $this->vendormobileListingRepo->create($data);
        
    //     $listing->load('brand', 'model');

    //     // Convert listing to array
    //     $listingArray = $listing->toArray();

    //     // Prepend brand & model at the top
    //     $response = array_merge(
    //     [
    //         'id' => $listing->id,
    //         'brand' => $listing->brand->name ?? null,
    //         'model' => $listing->model->name ?? null,
    //     ],
    //     // Remove brand_id & model_id
    //     Arr::except($listingArray, ['brand_id', 'model_id', 'brand', 'model']),
    //     [
    //         // Replace image paths with full asset URLs
    //         'image' => array_map(fn($path) => asset($path), $mediaPaths),
    //         'video' => array_map(fn($path) => asset($path), $videoPaths),
    //     ]);
    //     $radius = 50; // default radius in km
    //     $condition = $request->condition === 'New' ? 'brand-new' : 'Used';
    //     $customers = MobileRequest::with('customer')
    //         ->whereNotNull('latitude')
    //         ->whereNotNull('longitude')
    //         ->where('brand_id', $listing->brand->id)
    //         ->where('model_id', $listing->model->id)
    //         ->where('condition', $listing->condition)
    //         ->select('*', DB::raw("
    //                 (6371 * acos(
    //                     cos(radians($listing->latitude)) 
    //                     * cos(radians(latitude)) 
    //                     * cos(radians(longitude) - radians($listing->longitude)) 
    //                     + sin(radians($listing->latitude)) 
    //                     * sin(radians(latitude))
    //                 )) AS distance
    //             "))
    //         ->having('distance', '<=', $radius)
    //         ->orderBy('distance', 'asc')
    //         ->get();
    //         // Send email notifications
    //         $data = $customers->pluck('customer')
    //               ->filter()      // null remove karega
    //               ->unique('id')  // duplicate customers remove karega
    //               ->values()
    //               ->all();
    //         $listing->vendor_name = Auth::user()->name;
    //         $listing->brand_name = Brand::find($listing->brand_id)->name ?? 'Unknown Brand';
    //         $listing->model_name = MobileModel::find($listing->model_id)->name ?? 'Unknown Model';
    //         $this->sendBulkEmails($data,NotifyNearByCustomersOfRequestedMobile::class,['data' => $listing]);  
    //         // Send notifications
            
    //         $notification = Notification::create([
    //                 'user_type' => 'customers',
    //                 'title' => "New Mobile Listing",
    //                 'description' => "Good news! A nearby vendor has listed a mobile matching your request for a {$condition} {$listing->brand->name} {$listing->model->name}.",
    //             ]);

    //         foreach ($customers as $data) {
    //             NotificationTarget::create([
    //                 'notification_id' => $notification->id,
    //                 'targetable_id' => $data->customer->id,
    //                 'targetable_type' => 'App\Models\User',
    //                 'type' => 'vendor_mobile_listed',
    //                 'order_id' => $listing->id,
    //             ]);
    //             if (!empty($data->customer->fcm_token)) {
    //                 NotificationHelper::sendFcmNotification(
    //                     $data->customer->fcm_token,
    //                     "New Mobile Listing",
    //                     "Good news! A nearby vendor has listed a mobile matching your request for a {$condition} {$listing->brand->name} {$listing->model->name}.",
    //                     [
    //                         'type' => 'vendor_mobile_listed',
    //                         'order_id' => (string) $listing->id,
    //                     ]
    //                 );
    //             }
    //         }
    //     VendorSubscriptionProducts::create([
    //         'vendor_subscription_id' => VendorSubscription::where('vendor_id', $vendorId)->where('is_active', true)->first()->id,
    //         'mobile_id' => $listing->id,
    //     ]);

    //     return $response;
    // }
    public function createListing($request)
    {
        $vendorId = Auth::id();

        $activePlan = VendorSubscription::with('plan')
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->first();
            $mediaPaths = [];
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('admin/assets/images/users/'), $filename);
                    $mediaPaths[] = 'public/admin/assets/images/users/' . $filename;
                }
            }
            $videoPaths = [];
            if ($request->hasFile('video')) {
                foreach ($request->file('video') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('admin/assets/videos/'), $filename);
                    $videoPaths[] = 'public/admin/assets/videos/' . $filename;
                }
            }
        $brandId = $request->brand_id; // direct ID aa rahi hai
        $brand = Brand::find($brandId);
        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Brand ID'
            ], 422);
        }
        $modelName = trim($request->model);
        $model = MobileModel::where('brand_id', $brandId)
            ->whereRaw('LOWER(name) = ?', [strtolower($modelName)])
            ->first();
        if (!$model) {
            $model = MobileModel::create([
                'brand_id' => $brandId,
                'name' => $modelName,
            ]);
        }
        $data = [
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'storage' => $request->storage,
            'ram' => $request->ram,
            'price' => $request->price,
            'condition' => $request->condition,
            'about' => $request->about,
            'stock' => $request->stock,
            'pta_approved' => $request->pta_approved,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'vendor_id' => $vendorId,
            'image' => json_encode($mediaPaths),
            'video' => json_encode($videoPaths),

            // ❌ Removed fields
            // 'color' => $request->color,
            // 'processor' => $request->processor,
            // 'display' => $request->display,
            // 'charging' => $request->charging,
            // 'refresh_rate' => $request->refresh_rate,
            // 'main_camera' => $request->main_camera,
            // 'ultra_camera' => $request->ultra_camera,
            // 'telephoto_camera' => $request->telephoto_camera,
            // 'front_camera' => $request->front_camera,
            // 'build' => $request->build,
            // 'wireless' => $request->wireless,
            // 'ai_features' => $request->ai_features,
            // 'battery_health' => $request->battery_health,
            // 'os_version' => $request->os_version,
            // 'warranty_start' => $request->warranty_start,
            // 'warranty_end' => $request->warranty_end,
            // 'location' => $request->location,
        ];

        $listing = $this->vendormobileListingRepo->create($data);

        $listing->load('brand', 'model');

        $listingArray = $listing->toArray();

        $response = array_merge(
            [
                'id' => $listing->id,
                'brand' => $listing->brand->name ?? null,
                'model' => $listing->model->name ?? null,
            ],
            Arr::except($listingArray, ['brand_id', 'model_id', 'brand', 'model']),
            [
                'image' => array_map(fn($path) => asset($path), $mediaPaths),
                'video' => array_map(fn($path) => asset($path), $videoPaths),
            ]
        );

        // ✅ Distance logic will work now
        $radius = 50;
        $condition = $request->condition === 'New' ? 'brand-new' : 'Used';

        $customers = MobileRequest::with('customer')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('brand_id', $listing->brand->id)
            ->where('model_id', $listing->model->id)
            ->where('condition', $listing->condition)
            ->select('*', DB::raw("
                (6371 * acos(
                    cos(radians($listing->latitude)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($listing->longitude)) 
                    + sin(radians($listing->latitude)) 
                    * sin(radians(latitude))
                )) AS distance
            "))
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        $data = $customers->pluck('customer')->filter()->unique('id')->values()->all();

        $listing->vendor_name = Auth::user()->name;
        $listing->brand_name = Brand::find($listing->brand_id)->name ?? 'Unknown Brand';
        $listing->model_name = MobileModel::find($listing->model_id)->name ?? 'Unknown Model';

        $this->sendBulkEmails($data, NotifyNearByCustomersOfRequestedMobile::class, ['data' => $listing]);

        $notification = Notification::create([
            'user_type' => 'customers',
            'title' => "New Mobile Listing",
            'description' => "Good news! A nearby vendor has listed a mobile matching your request for a {$condition} {$listing->brand->name} {$listing->model->name}.",
        ]);

        foreach ($customers as $data) {
            NotificationTarget::create([
                'notification_id' => $notification->id,
                'targetable_id' => $data->customer->id,
                'targetable_type' => 'App\Models\User',
                'type' => 'vendor_mobile_listed',
                'order_id' => $listing->id,
            ]);

            if (!empty($data->customer->fcm_token)) {
                NotificationHelper::sendFcmNotification(
                    $data->customer->fcm_token,
                    "New Mobile Listing",
                    "Good news! A nearby vendor has listed a mobile matching your request for a {$condition} {$listing->brand->name} {$listing->model->name}.",
                    [
                        'type' => 'vendor_mobile_listed',
                        'order_id' => (string) $listing->id,
                    ]
                );
            }
        }

        VendorSubscriptionProducts::create([
            'vendor_subscription_id' => $activePlan->id,
            'mobile_id' => $listing->id,
        ]);

        return $response;
    }
    public function updateListing($request, $id)
    {
        $vendorId = Auth::id();

        $listing = $this->vendormobileListingRepo->find($id);

        if (!$listing) {
            throw new ModelNotFoundException('Listing not found');
        }

        if ($listing->vendor_id != $vendorId) {
            throw new AuthorizationException('Unauthorized');
        }

        /* ---------------- IMAGE UPLOAD ---------------- */
        if ($request->hasFile('image')) {
            $images = [];
            foreach ($request->file('image') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move(public_path('admin/assets/images/users/'), $filename);
                $images[] = 'public/admin/assets/images/users/'.$filename;
            }
        } else {
            $images = json_decode($listing->image, true) ?? [];
        }

        /* ---------------- VIDEO UPLOAD ---------------- */
        if ($request->hasFile('video')) {
            $videos = [];
            foreach ($request->file('video') as $file) {
                $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move(public_path('admin/assets/videos/'), $filename);
                $videos[] = 'public/admin/assets/videos/'.$filename;
            }
        } else {
            $videos = json_decode($listing->video, true) ?? [];
        }

        /* ---------------- UPDATE DATA ---------------- */
        $fields = [
            'brand_id', 'model_id', 'storage', 'ram', 'color', 'price', 'condition', 
            'about', 'processor', 'display', 'charging', 'refresh_rate', 'main_camera',
            'ultra_camera', 'telephoto_camera', 'front_camera', 'build', 'wireless', 
            'stock', 'ai_features', 'battery_health', 'os_version', 'pta_approved', 
            'location', 'latitude', 'longitude'
        ];

        $data = [];

        foreach ($fields as $field) {
            // If request has this field, use it, otherwise keep old value
            $data[$field] = $request->has($field) ? $request->$field : $listing->$field;
        }

        $data['image'] = json_encode($images);
        $data['video'] = json_encode($videos);

        $location = $request->has('location') ? $request->location : $listing->location;
        $data['city'] = $this->updateCitiesFromLocation($location);

        $updated = $this->vendormobileListingRepo->update($id, $data);
        $updated->refresh();
        $updated->load('brand', 'model');

        /* ---------------- FULL RESPONSE ---------------- */
        return [
            'id'              => $updated->id,
            'brand'           => $updated->brand?->name,
            'model'           => $updated->model?->name,
            'storage'         => $updated->storage,
            'ram'             => $updated->ram,
            'color'           => $updated->color,
            'price'           => $updated->price,
            'condition'       => $updated->condition,
            'about'           => $updated->about,
            'processor'       => $updated->processor,
            'display'         => $updated->display,
            'charging'        => $updated->charging,
            'refresh_rate'    => $updated->refresh_rate,
            'main_camera'     => $updated->main_camera,
            'ultra_camera'    => $updated->ultra_camera,
            'telephoto_camera'=> $updated->telephoto_camera,
            'front_camera'    => $updated->front_camera,
            'build'           => $updated->build,
            'wireless'        => $updated->wireless,
            'stock'           => $updated->stock,
            'ai_features'     => $updated->ai_features,
            'battery_health'  => $updated->battery_health,
            'os_version'      => $updated->os_version,
            'pta_approved'    => $updated->pta_approved,
            'location'        => $updated->location,
            'latitude'        => $updated->latitude,
            'longitude'       => $updated->longitude,

            'image' => array_map(fn($path) => asset($path), json_decode($updated->image, true) ?? []),
            'video' => array_map(fn($path) => asset($path), json_decode($updated->video, true) ?? []),

            'created_at' => $updated->created_at,
            'updated_at' => $updated->updated_at,
        ];
    }


    public function previewListing($id)
    {
        $listing = $this->vendormobileListingRepo->findWithRelations($id);

        $images = json_decode($listing->image, true) ?? [];
        $videos = json_decode($listing->video, true) ?? [];

        return [
            'id'        => $listing->id,
            'brand'     => $listing->brand ? $listing->brand->name : null,
            'model'     => $listing->model ? $listing->model->name : null,
            'storage'   => $listing->storage,
            'color'     => $listing->color,
            'ram'       => $listing->ram,
            'price'     => $listing->price,
            'condition' => $listing->condition,
            'about'     => $listing->about,
            'processor' => $listing->processor,
            'display'   => $listing->display,
            'charging'  => $listing->charging,
            'refresh_rate' => $listing->refresh_rate,
            'main_camera' => $listing->main_camera,
            'ultra_camera' => $listing->ultra_camera,
            'telephoto_camera' => $listing->telephoto_camera,
            'front_camera' => $listing->front_camera,
            'build'     => $listing->build,
            'wireless'  => $listing->wireless,
            'stock'     => $listing->stock,
            'ai_features' => $listing->ai_features,
            'battery_health' => $listing->battery_health,
            'os_version' => $listing->os_version,
            'warranty_start' => $listing->warranty_start,
            'warranty_end' => $listing->warranty_end,
            // 'quantity' => $listing->quantity,
            'vendor_id' => $listing->vendor_id,
            'image'     => array_map(fn($path) => asset($path), $images),
            'video'     => array_map(fn($path) => asset($path), $videos),
        ];
    }
    
    public function deactivateListing($id)
    {
        $vendorId = Auth::id();

        $listing = $this->vendormobileListingRepo->find($id);

        if (!$listing) {
            throw new ModelNotFoundException('Listing not found');
        }

        // 🔐 Ownership check
        if ($listing->vendor_id !== $vendorId) {
            throw new AuthorizationException('Unauthorized');
        }

        if ($listing->status == 1) {
            // currently deactivated → activate it
            $listing->status = 0;
            $state = 'activated';
        } else {
            // currently active → deactivate it
            $listing->status = 1;
            $state = 'deactivated';
        }

        $listing->save();

        return [
            'id'     => $listing->id,
            'status' => $listing->status,
            'state'  => $state
        ];
    }

public function updateCitiesFromLocation($location)
{
    $cities = [
        'Lahore','Raiwind','Shahdara','Kasur','Chunian','Pattoki','Kot Radha Kishan',
        'Faisalabad','Jaranwala','Samundri','Tandlianwala','Chak Jhumra',
        'Gojra','Toba Tek Singh','Kamalia','Pir Mahal',
        'Rawalpindi','Taxila','Wah Cantonment','Kahuta','Kotli Sattian','Murree',
        'Multan','Shujabad','Jalalpur Pirwala',
        'Gujranwala','Kamoke','Wazirabad','Nowshera Virkan',
        'Sialkot','Daska','Sambrial','Pasrur',
        'Narowal','Shakargarh','Zafarwal',
        'Gujrat','Kharian','Sarai Alamgir','Lalamusa',
        'Mandi Bahauddin','Phalia','Malakwal',
        'Sargodha','Bhalwal','Sillanwali','Shahpur','Kot Momin',
        'Bhakkar','Darya Khan','Kalur Kot',
        'Mianwali','Isa Khel','Piplan',
        'Attock','Hazro','Hasan Abdal','Jand',
        'Jhelum','Dina','Sohawa',
        'Chakwal','Kallar Kahar','Choa Saidan Shah','Lawa',
        'Bahawalpur','Ahmadpur East','Yazman','Hasilpur','Khairpur Tamewali',
        'Bahawalnagar','Chishtian','Haroonabad','Fort Abbas','Minchinabad',
        'Rahim Yar Khan','Sadiqabad','Khanpur','Liaquatpur',
        'Okara','Depalpur','Renala Khurd','Haveli Lakha',
        'Sahiwal','Chichawatni',
        'Pakpattan','Arifwala',
        'Vehari','Burewala','Mailsi',
        'Khanewal','Kabirwala','Jahanian','Mian Channu',
        'Lodhran','Dunyapur','Kahror Pakka',
        'Dera Ghazi Khan','Taunsa',
        'Muzaffargarh','Kot Addu','Alipur','Jatoi',
        'Layyah','Karor Lal Esan',
        'Rajanpur','Jampur','Rojhan',
        'Sheikhupura','Ferozewala','Muridke','Sharaqpur',
        'Nankana Sahib','Sangla Hill','Safdarabad',
        'Hafizabad','Pindi Bhattian',
        'Chiniot','Lalian','Bhawana',
        'Karachi','Hyderabad','Sukkur','Larkana','Nawabshah','Mirpur Khas',
        'Jacobabad','Shikarpur','Khairpur','Dadu','Badin','Thatta','Umerkot',
        'Tando Adam','Tando Allahyar','Tando Muhammad Khan',
        'Kashmore','Kandhkot','Ghotki','Daharki','Rohri','Pano Aqil',
        'Kotri','Jamshoro','Sehwan','Matiari','Hala',
        'Sanghar','Shahdadpur','Sinjhoro','Khipro','Jhol',
        'Digri','Kunri','Samaro','Chhor','Mithi','Islamkot','Diplo','Nagarparkar',
        'Ratodero','Dokri','Naudero','Warah','Qambar','Shahdadkot',
        'Nasirabad','Mehar','Khairpur Nathan Shah',
        'Ubauro','Mirpur Mathelo',
        'Keti Bandar','Gharo','Mirpur Bathoro','Sujawal','Jati',
        'Peshawar','Mardan','Abbottabad','Mingora','Saidu Sharif',
        'Kohat','Bannu','Dera Ismail Khan','Charsadda','Nowshera',
        'Mansehra','Haripur','Swabi','Tank','Hangu',
        'Battagram','Shangla','Dir','Timergara',
        'Balakot','Oghi','Batkhela','Chakdara',
        'Drosh','Chitral','Upper Dir','Lower Dir',
        'Lakki Marwat','Parachinar','Kurram','Orakzai',
        'Wana','South Waziristan','Miranshah','North Waziristan',
        'Shabqadar','Tangi','Takht Bhai','Katlang','Rustam',
        'Topi','Zaida','Tordher','Ghazi',
        'Kabal','Matta','Khwazakhela','Buner','Daggar',
        'Quetta','Gwadar','Turbat','Khuzdar','Chaman','Sibi','Zhob','Loralai',
        'Dera Murad Jamali','Hub','Panjgur','Nushki','Dalbandin','Kharan',
        'Mastung','Kalat','Surab','Uthal','Bela','Pasni','Ormara',
        'Awaran','Hoshab','Washuk','Dera Allah Yar','Jaffarabad',
        'Jhal Magsi','Mach','Duki','Musakhel','Barkhan',
        'Islamabad','Gilgit','Skardu','Hunza','Chilas','Ghanche','Ghizer',
        'Khaplu','Shigar','Astore','Darel','Tangir',
        'Muzaffarabad','Mirpur','Kotli','Bagh','Rawalakot',
        'Bhimber','Hattian Bala','Neelum','Athmuqam',
        'Forward Kahuta','Dadyal','Samahni'
    ];

    $locationLower = strtolower($location ?? '');
    $matchedCity = null;

    foreach ($cities as $city) {
        if (preg_match("/\b" . preg_quote(strtolower($city), '/') . "\b/", $locationLower)) {
            $matchedCity = $city;
            break;
        }
    }

    return $matchedCity;
}

}
