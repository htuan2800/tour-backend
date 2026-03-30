<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Staff;
use App\Models\UserProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;
use Exception;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function registerCustomer(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $customerRole = Role::where('name', 'CUSTOMER')->firstOrFail();
            $user = User::create([
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $customerRole->role_id,
                'is_active' => false,
            ]);

            $date = Carbon::parse($data['dateOfBirth'])->format('Y-m-d');
            $user->customer()->create([
                'full_name' => $data['fullName'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'date_of_birth' => $date,
            ]);


            // 3. Kích hoạt sự kiện gửi mail
            event(new Registered($user));

            return $user;
        });
    }

    public function login(array $credentials, string $page)
    {
        $user = User::withTrashed()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng.', 'code' => 401];
        }

        if ($user->trashed()) {
            return ['success' => false, 'message' => 'Tài khoản của bạn đã bị xóa khỏi hệ thống.', 'code' => 403];
        }

        $roleName = $user->role->name ?? '';
        if ($page == "admin") {
            $notAllowedRoles = ['CUSTOMER'];
            if (in_array($roleName, $notAllowedRoles)) {
                return ['success' => false, 'message' => 'Tài khoản của bạn không có quyền truy cập vào khu vực này.', 'code' => 403];
            }
        } else {
            $allowedRoles = ['CUSTOMER', 'ADMIN'];
            if (!in_array($roleName, $allowedRoles)) {
                return ['success' => false, 'message' => 'Tài khoản của bạn không có quyền truy cập vào khu vực này.', 'code' => 403];
            }
        }

        if (!$user->hasVerifiedEmail()) {
            event(new Registered($user));
            return [
                'success' => false,
                'message' => 'Tài khoản chưa kích hoạt. Vui lòng kiểm tra email.',
                'code' => 403,
                'data' => ['email' => $user->email] // Trả về data để Frontend hiện nút "Gửi lại mail"
            ];
        }

        if (!$user->is_active) {
            return ['success' => false, 'message' => 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ hỗ trợ.', 'code' => 403];
        }

        // 7. Vượt qua mọi chốt chặn -> Đăng nhập và tạo Token
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        // Gọi attempt để Laravel ghi nhận trạng thái đăng nhập hợp lệ
        $guard->attempt($credentials);

        $guard->factory()->setTTL(60);
        $accessToken = $guard->login($user);

        $guard->factory()->setTTL(60 * 24 * 365);
        $refreshToken = $guard->login($user);

        return [
            'success' => true,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function loginWithSocial(string $providerName, $socialUser)
    {
        $providerId = $socialUser->getId();
        $email = $socialUser->getEmail();

        $existingLink = UserProvider::where('provider_name', $providerName)
            ->where('provider_id', $providerId)
            ->first();

        if ($existingLink) {
            return $existingLink->user;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->providers()->create([
                'provider_name' => $providerName,
                'provider_id'   => $providerId,
            ]);
            return $user;
        }

        return DB::transaction(function () use ($providerName, $email, $socialUser, $providerId) {

            $customerRole = Role::where('name', 'CUSTOMER')->firstOrFail();

            $user = User::create([
                'email'             => $email,
                'password'          => null,
                'role_id'           => $customerRole->role_id,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $user->customer()->create([
                'full_name' => $socialUser->getName()
            ]);

            $user->providers()->create([
                'provider_name' => $providerName,
                'provider_id'   => $providerId,
            ]);

            return $user;
        });
    }

    public function checkDeleteUser(User $user) {}

    public function resetPassword(array $credentials)
    {
        return Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password
                    //'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Gửi event để báo user đã đổi pass xong (Optional)
                event(new PasswordReset($user));
            }
        );
    }
}
