<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Auth\Events\Verified;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $authService;
    protected $userService;
    protected $supportedProviders = ['google', 'facebook'];
    public function __construct(AuthService $authService, UserService $userService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request)
    {
        // 1. Validate dữ liệu
        $validated = $request->validated();

        try {
            // 2. Gọi Service để xử lý logic
            $user = $this->authService->registerCustomer($validated);

            return $this->success($user, 'Đăng ký thành công', 201);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu Transaction fail
            return $this->error('Đăng ký thất bại: ' . $e->getMessage(), 500);
        }
    }
    // API Login
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $result = $this->authService->login($credentials, 'customer');

        if (!$result['success']) {
            $data = $result['data'] ?? []; 
            return $this->error($result['message'], $result['code'], $data);
        }

        return $this->respondWithTokens($result['access_token'], $result['refresh_token']);
    }

    public function loginForAdmin(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $result = $this->authService->login($credentials, 'admin');

        if (!$result['success']) {
            $data = $result['data'] ?? []; 
            return $this->error($result['message'], $result['code'], $data);
        }

        return $this->respondWithTokens($result['access_token'], $result['refresh_token']);
    }

    // API lấy thông tin user
    public function me()
    {

        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();
        $user->load(['customer', 'staff', 'role.permissions.feature']);

        return $this->success(new UserResource($user), 'Lấy thông tin thành công');
    }

    // API Logout (Xóa cookie)
    public function logout()
    {
        Auth::guard('api')->logout();
        $cookie = cookie()->forget('refreshToken');

        return response()->json(['message' => 'Successfully logged out'])
            ->withCookie($cookie);
    }

    public function refresh(Request $request)
    {
        // Lấy refresh token từ Cookie
        $refreshToken = $request->cookie('refreshToken');

        if (!$refreshToken) {
            return $this->error('Refresh Token not found', 401);
        }

        try {
            /** @var JWTGuard $guard */
            // Set token vào guard để check
            $guard = Auth::guard('api');
            $guard->setToken($refreshToken);

            // Kiểm tra xem Refresh Token này còn hạn không và lấy user
            if (!$guard->check()) {
                return $this->error('Invalid or expired refresh token', 401);
            }

            //Tạo Access Token mới (1 giờ)
            $user = $guard->user();
            $guard->factory()->setTTL(60);
            $newAccessToken = $guard->login($user);

            return $this->success([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => 60 * 60
            ], 'Access Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->error('Could not refresh token: ' . $e->getMessage(), 500);
        }
    }

    // Hàm trả về JSON + Cookie
    protected function respondWithTokens($accessToken, $refreshToken)
    {
        $cookie = cookie(
            'refreshToken',
            $refreshToken,
            60 * 24 * 365,
            '/',
            null,
            false,
            true
        );

        $data = [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60
        ];

        return $this->success($data, 'Đăng nhập thành công')->withCookie($cookie);
    }


    public function verifyEmail(Request $request, $id, $hash)
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL'));

        $user = $this->userService->findUserById($id);
        if (!$user) {
            return redirect("$frontendUrl?status=invalid_user");
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect("$frontendUrl?status=invalid_hash");
        }

        if ($user->hasVerifiedEmail()) {
            return redirect("$frontendUrl?status=already_verified");
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $this->userService->activeUser($user);
        }

        return redirect("$frontendUrl/login?status=success");
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = $this->userService->findUserByEmail($request->email);

        // 1. Kiểm tra user có tồn tại không
        if (!$user) {
            return $this->error('Email không tồn tại trong hệ thống', 404);
        }

        // 2. Kiểm tra đã verify chưa
        if ($user->hasVerifiedEmail()) {
            return $this->error('Tài khoản này đã được kích hoạt rồi.', 400);
        }

        // 3. Gửi lại email
        $user->sendEmailVerificationNotification();

        return $this->success(null, 'Link kích hoạt đã được gửi lại vào email của bạn.');
    }

    public function getSocialAuthUrl($provider)
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return $this->error('Phương thức đăng nhập không được hỗ trợ.', 400);
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $this->success([
            'url' => $driver->stateless()->redirect()->getTargetUrl()
        ], 'Lấy link login thành công');
    }

    public function socialLoginCallback(Request $request, $provider)
    {
        if (!in_array($provider, $this->supportedProviders)) {
            return $this->error('Phương thức đăng nhập không được hỗ trợ.', 400);
        }

        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $socialUser = $driver->stateless()->user();

            $user = $this->authService->loginWithSocial($provider, $socialUser);

            $baseUrl = 'http://localhost:5174/oauth2/redirect';

            if ($user->trashed()) {
                return redirect($baseUrl . '?error=account_deleted');
            }

            if (!$user->is_active) {
                return redirect($baseUrl . '?error=account_locked');
            }
            /** @var JWTGuard $guard */
            $guard = Auth::guard('api');

            // Access Token
            $guard->factory()->setTTL(60);
            $accessToken = $guard->login($user);

            // Refresh Token
            $guard->factory()->setTTL(60 * 24 * 365);
            $refreshToken = $guard->login($user);

            $cookie = cookie(
                'refreshToken',
                $refreshToken,
                60 * 24 * 365,
                '/',
                null,
                false,
                true
            );

            return redirect($baseUrl . '?access_token=' . $accessToken)->withCookie($cookie);
        } catch (\Exception $e) {
            return $this->error('Đăng nhập ' . ucfirst($provider) . ' thất bại: ' . $e->getMessage(), 400);
        }
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email này chưa được đăng ký trong hệ thống.'
        ]);

        $email = $request->email;
        $token = Str::random(60); // Tạo chuỗi ngẫu nhiên dài 60 ký tự

        // Xóa token cũ của user này (nếu có) để tránh spam
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Lưu token mới vào DB
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        $resetLink = env('FRONTEND_URL', 'http://localhost:5173') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
        Mail::send([], [], function ($message) use ($email, $resetLink) {
            $message->to($email)
                ->subject('Yêu cầu Đặt lại mật khẩu')
                ->html("
                    <h2>Xin chào!</h2>
                    <p>Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng click vào nút bên dưới để tiến hành:</p>
                    <a href='{$resetLink}' style='padding: 10px 20px; background: #00529C; color: white; text-decoration: none; border-radius: 5px;'>Đặt lại mật khẩu</a>
                    <p>Link này sẽ hết hạn trong 60 phút.</p>
                ");
        });

        return response()->json(['message' => 'Link đặt lại mật khẩu đã được gửi vào email của bạn!'], 200);
    }

    // 2. Xử lý lưu mật khẩu mới
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|min:6|confirmed', // Yêu cầu có trường password_confirmation
        ]);

        // Kiểm tra token có hợp lệ không
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // Kiểm tra thời gian hết hạn (VD: 60 phút)
        $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
        if ($tokenCreatedAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Token đã hết hạn. Vui lòng gửi yêu cầu mới.'], 400);
        }

        // Cập nhật mật khẩu mới cho User
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token đi để không dùng lại được nữa
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Đổi mật khẩu thành công! Bạn có thể đăng nhập ngay.'], 200);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $credentials = $request->validated();
        $status = $this->authService->resetPassword($credentials);

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Mật khẩu đã được thay đổi thành công!');
        }

        return $this->error('Đặt lại mật khẩu thất bại: ' . __($status), 400);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->error('Mật khâu không hợp lệ', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->success(null, 'Mật khâu đã đổi.');
    }
}
