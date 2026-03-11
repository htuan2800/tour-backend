<?php

namespace App\Models;

use App\Traits\HasPermissionsTrait;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;  //hỗ trợ tạo các mẫu dữ liệu thử nghiệm
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable; //hỗ trợ xác thực người dùng
use Illuminate\Notifications\Notifiable;               //hỗ trợ gửi thông báo cho người dùng
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject; //hỗ trợ xác thực người dùng bằng JWT
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasPermissionsTrait;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use SoftDeletes;
    protected $primaryKey = 'user_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role_id',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //Relations
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function providers()
    {
        return $this->hasMany(UserProvider::class, 'user_id');
    }

    // 1. Lấy ID để nhét vào token (Subject Claim)
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Trả về user_id
    }

    // Token Payload
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        // Tạo URL trỏ về trang Reset Password của React Frontend
        $url = env('FRONTEND_URL') . '/auth/reset-password?token=' . $token . '&email=' . $this->email;
        $this->notify(new ResetPassword($url));
    }

    public function getFullNameAttribute()
    {
        // Nếu có quan hệ customer -> lấy tên customer
        if ($this->customer) {
            return $this->customer->full_name;
        }

        // Nếu có quan hệ staff -> lấy tên staff
        if ($this->staff) {
            return $this->staff->full_name;
        }

        return null;
    }

    //Thêm vào mảng $appends để nó hiện ra trong JSON
    protected $appends = ['full_name'];
}
