<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật trạng thái Booking</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #2563eb; padding: 30px 20px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 14px; margin: 15px 0; }
        /* Các màu trạng thái */
        .status-paid { background-color: #dcfce7; color: #166534; }
        .status-verifying { background-color: #fef08a; color: #854d0e; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        
        .booking-details { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-top: 20px; }
        .booking-details table { width: 100%; border-collapse: collapse; }
        .booking-details td { padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .booking-details td:last-child { text-align: right; font-weight: 500; }
        .booking-details tr:last-child td { border-bottom: none; }
        .total-row td { font-size: 18px; font-weight: bold; color: #2563eb; }
        
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 13px; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 500; margin-top: 20px; }
    </style>
</head>
<body>

<div class="email-container">
    <div class="header">
        <h1>Travel Company</h1>
    </div>

    <div class="content">
        <p>Xin chào <strong>{{ $booking->customer_name ?? 'Quý khách' }}</strong>,</p>
        <p>Chúng tôi xin thông báo đơn hàng <strong>#{{ $booking->booking_id }}</strong> của bạn vừa được cập nhật trạng thái mới.</p>
        
        <div style="text-align: center;">
            <span class="status-badge {{ $statusData['css_class'] }}">
                {{ $statusData['title'] }}
            </span>
            <p style="margin-top: 5px; color: #64748b; font-size: 14px;">{{ $statusData['message'] }}</p>
        </div>

        <div class="booking-details">
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #1e293b;">Chi tiết dịch vụ</h3>
            <table>
                <tr>
                    <td>Chuyến đi</td>
                    <td>{{ $booking->schedule->tour->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Ngày khởi hành</td>
                    <td>{{ \Carbon\Carbon::parse($booking->schedule->departure_date)->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Hành khách</td>
                    <td>{{ $booking->number_of_adults }} Người lớn, {{ $booking->number_of_children }} Trẻ em</td>
                </tr>
                <tr class="total-row">
                    <td>Tổng tiền</td>
                    <td>{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email support@travelcompany.com hoặc hotline 1900 xxxx.</p>
        <p>© {{ date('Y') }} Travel Company. All rights reserved.</p>
    </div>
</div>

</body>
</html>