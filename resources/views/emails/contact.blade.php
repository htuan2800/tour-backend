<!DOCTYPE html>
<html>
<head>
    <title>Liên hệ mới từ khách hàng</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>Bạn nhận được một liên hệ mới từ Website</h2>
    
    <p><strong>Họ tên:</strong> {{ $contactData['fullName'] }}</p>
    <p><strong>Email:</strong> {{ $contactData['email'] }}</p>
    <p><strong>Số điện thoại:</strong> {{ $contactData['phone'] }}</p>
    
    @if(!empty($contactData['address']))
        <p><strong>Địa chỉ:</strong> {{ $contactData['address'] }}</p>
    @endif

    <hr>
    <h3>Nội dung liên hệ:</h3>
    <p><strong>Chủ đề:</strong> {{ $contactData['subject'] }}</p>
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px;">
        {!! nl2br(e($contactData['content'])) !!}
    </div>
</body>
</html>