<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Illuminate\Validation\Rule;
class BookingRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_id' => 'required|exists:tours,tour_id',
            'destination_id' => 'required|exists:locations,location_id',
            'schedule_id'           => 'required|exists:tour_schedules,schedule_id', 
            'contact.fullName'      => 'required|string|max:255',
            'contact.phone'         => 'required|string|max:20',
            'contact.email'         => 'required|email',
            'contact.address'       => 'required|string|max:255',
            'adults'                => 'required|array|min:1',
            'adults.*.fullName'     => 'required|string|max:255',
            'adults.*.dob'          => 'required|date',
            'adults.*.gender'       => 'required|string',
            'children'              => 'nullable|array',
            'paymentMethod'         => 'required|in:MOMO,VNPAY,OFFICE,CASH',
            'note'                  => 'nullable|string',
            'voucherCode' => 'nullable|string|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'tour_id.required' => 'Vui lòng chọn tour.',
            'tour_id.exists'   => 'Tour không tồn tại.',
            'destination_id.required' => 'Vui lòng chọn điểm đến.',
            'destination_id.exists'   => 'Điểm đến không tồn tại.',
            'schedule_id.required' => 'Vui lòng chọn lịch trình.',
            'schedule_id.exists'   => 'Lịch trình không tồn tại.',
            'contact.fullName.required' => 'Vui lòng nhập họ tên người liên hệ.',
            'contact.phone.required'    => 'Vui lòng nhập số điện thoại người liên hệ.',
            'contact.email.required'    => 'Vui lòng nhập email người liên hệ.',
            'contact.email.email'       => 'Email người liên hệ không đúng định dạng.',
            'contact.address.required'  => 'Vui lòng nhập địa chỉ người liên hệ.',
            'contact.address.max'       => 'Địa chỉ người liên hệ không được vượt quá 255 ký tự.',
            'adults.required'           => 'Vui lòng thêm ít nhất một hành khách người lớn.',
            'adults.*.fullName.required'=> 'Vui lòng nhập họ tên cho tất cả hành khách người lớn.',
            'adults.*.dob.required'     => 'Vui lòng nhập ngày sinh cho tất cả hành khách người lớn.',
            'adults.*.gender.required'  => 'Vui lòng chọn giới tính cho tất cả hành khách người lớn.',
            'paymentMethod.required'    => 'Vui lòng chọn phương thức thanh toán.',
            'paymentMethod.in'          => 'Phương thức thanh toán không hợp lệ.',
            'voucherCode.string' => 'Mã giảm giá phải là một chuỗi.',
            'voucherCode.max' => 'Mã giảm giá không được vượt quá 50 ký tự.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        throw new HttpResponseException(
            $this->error('Dữ liệu không hợp lệ', 422, $errors)
        );
    }
}
