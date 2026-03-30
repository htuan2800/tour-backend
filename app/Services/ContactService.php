<?php
namespace App\Services;

use App\Mail\ContactEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactService
{
    public function sendContactEmail(array $data): bool
    {
        try {
            // Thay địa chỉ email này bằng Email của Admin/Công ty bạn
            $adminEmail = 'huynhngoctuan48@gmail.com';

            Mail::to($adminEmail)->send(new ContactEmail($data));

            return true;
        } catch (\Exception $e) {
            // Ghi log lại lỗi để dev debug, không ném thẳng ra cho user
            Log::error('Lỗi gửi email liên hệ: ' . $e->getMessage());
            return false;
        }
    }
}