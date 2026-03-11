<?php

namespace App\Services;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CouponService
{

    public function getPagenatedCoupon(int $limit, ?string $search)
    {
        $query = Coupon::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('coupon_id', 'DESC')->paginate($limit);
    }


    public function getCouponForPayment()
    {
        $now = Carbon::now();
        $coupons = Coupon::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->whereRaw('usage_count < usage_limit')
            ->get();
        return $coupons;
    }

    public function createCoupon(array $data)
    {
        return DB::transaction(function () use ($data) {
            $coupon = Coupon::create(
                [
                    'code' => $data['code'],
                    'description' => $data['description'],
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_value'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'usage_limit' => $data['usage_limit'],
                    'usage_count' => 0,
                    'is_active' => true,
            ]);
            return $coupon;
        });
    }

    public function findCouponById(string $id)
    {
        return Coupon::findOrFail($id);
    }

    public function updateCoupon(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $coupon = Coupon::where('coupon_id', $id)->firstOrFail();
            $coupon->update(
                [
                    'code' => $data['code'],
                    'description' => $data['description'],
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_value'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'usage_limit' => $data['usage_limit']
                ]
            );

            return $coupon;
        });
    }

    public function toggleStatusCoupon(Coupon $coupon): void
    {
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();
    }

    public function deleteCoupon(Coupon $coupon): void
    {
        $coupon->delete();
    }
}
