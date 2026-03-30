<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $statusData; // Biến này sẽ truyền ra file Blade

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        // Tự xử lý data giao diện ngay lúc khởi tạo Mail
        $this->statusData = $this->getStatusData($booking->status); 
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cập nhật trạng thái đơn hàng #' . $this->booking->id,
        );
    }

    // 4. Chỉ định file giao diện HTML (file blade mà mình làm cho bạn ở trên)
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_status',
        );
    }

    public function getStatusData(string $status): array
    {
        return match ($status) {
            'PAID' => [
                'title' => 'Đã thanh toán',
                'message' => 'Cảm ơn bạn đã thanh toán. Chúc bạn có một chuyến đi vui vẻ!',
                'css_class' => 'status-paid'
            ],
            'VERIFYING' => [
                'title' => 'Đang xử lý',
                'message' => 'Chúng tôi đã nhận được thông tin và đang xác nhận dịch vụ cho bạn.',
                'css_class' => 'status-verifying'
            ],
            'CANCELLED' => [
                'title' => 'Đã hủy',
                'message' => 'Rất tiếc, đơn hàng của bạn đã bị hủy. Tiền sẽ được hoàn lại (nếu có) theo chính sách.',
                'css_class' => 'status-cancelled'
            ],
            'EXPIRED' => [
                'title' => 'Hết hạn thanh toán',
                'message' => 'Đơn hàng đã hết thời gian giữ chỗ do chưa được thanh toán.',
                'css_class' => 'status-cancelled'
            ],
            default => [
                'title' => 'Chờ thanh toán',
                'message' => 'Vui lòng hoàn tất thanh toán để giữ chỗ.',
                'css_class' => 'status-verifying'
            ]
        };
    }
}
