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
