<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Repositories\Api\Interfaces\HomeRepositoryInterface;

class HomeController extends Controller
{
    protected $homeRepository;

    public function __construct(HomeRepositoryInterface $homeRepository)
    {
        $this->homeRepository = $homeRepository;
    }

    // public function homeScreen(Request $request)
    // {
    //     try {
    //         $data = $this->homeRepository->getHomeScreenData($request);
    //         return ResponseHelper::success($data, 'Home screen data retrieved successfully', null, 200);
    //     } catch (\Exception $e) {
    //         return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving home screen data', 'error', 500);
    //     }
    // }

    public function allVendors()
    {
        try {
            $data = Vendor::select('id', 'name', 'email', 'phone', 'location')->where('status', 'activated')->latest()->get();
            return ResponseHelper::success($data, 'Vendors retrieved successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving vendors', 'error', 500);
        }
    }

    public function getNearbyListings(Request $request)
    {
        try {
            $data = $this->homeRepository->getNearbyListings($request);
            return ResponseHelper::success($data, 'Nearby listings fetched successfully', null, 200);
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return ResponseHelper::error($e->getMessage(), 'Failed to fetch nearby listings', 'error', 500);
        }
    }

    public function getTopSellingListings(Request $request)
    {
        try {
            $data = $this->homeRepository->getTopSellingListings($request);
            return ResponseHelper::success($data, 'Top selling listings fetched successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'Failed to fetch top selling listings', 'error', 500);
        }
    }

    public function deviceDetails($id)
    {
        try {
            $data = $this->homeRepository->getDeviceDetails($id);
            return ResponseHelper::success($data, 'Device details retrieved successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving device details', 'error', 500);
        }
    }

    public function vendorMobileDeviceDetails($id)
    {
        try {
            $data = $this->homeRepository->getvendorDeviceDetails($id);
            return ResponseHelper::success($data, 'Device details retrieved successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving device details', 'error', 500);
        }
    }
}
