<?php

namespace App\Services\Api;

use Illuminate\Http\Request;
use App\Repositories\Api\Interfaces\AuthRepositoryInterface;

class AuthService
{
    protected $authRepo;
    public function __construct(AuthRepositoryInterface $authRepo)
    {
        $this->authRepo = $authRepo;
    }
    public function register(array $request)
    {
        return $this->authRepo->register($request);
    }
    public function login(array $request)
    {
        return $this->authRepo->login($request);
    }
    public function sendOtp(array $request)
    {
        return $this->authRepo->sendOtp($request);
    }
    public function verifyOtp(Request $request)
    {
        return $this->authRepo->verifyOtp($request);
    }

    public function resendOtp(array $request)
    {
        return $this->authRepo->resendOtp($request);
    }
    public function forgotPasswordResendOtp(array $data)
    {
        return $this->authRepo->forgotPasswordResendOtp($data);
    }

    public function forgotPasswordSendOtp(array $request)
    {
        return $this->authRepo->forgotPasswordSendOtp($request);
    }

    public function forgotPasswordVerifyOtp(array $request)
    {
        return $this->authRepo->forgotPasswordVerifyOtp($request);
    }

    public function forgotPasswordReset($request)
    {
        return $this->authRepo->forgotPasswordReset($request);
    }


    public function logout()
    {
        return $this->authRepo->logout();
    }
    public function updateProfile(array $request)
    {
        return $this->authRepo->updateProfile($request);
    }
    public function changePassword(array $request)
    {
        return $this->authRepo->changePassword($request);
    }
    public function checkEmail($request)
    {
        return $this->authRepo->checkEmail($request->email);
    }

    public function checkUser($request)
    {
        return $this->authRepo->checkUser($request->email);
    }
}
