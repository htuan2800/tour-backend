<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; // Import Trait để trả về lỗi đúng format

class RegisterRequest extends FormRequest
{
    use ApiResponse; // Sử dụng Trait để format lỗi cho đồng bộ

    /**
     * Có cho phép user chạy request này không?
     * Return true = cho phép tất cả (Public API)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa luật validate
     */
    public function rules(): array
    {
        return [
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'fullName'   => 'required|string|max:100',
            'phone'      => 'required|string',
            'address'    => 'nullable|string|max:255',
            'dateOfBirth' => 'nullable|date',
            // 'role'      => 'required|in:CUSTOMER,SALE_STAFF,OPERATION_STAFF,GUIDE_STAFF',
        ];
    }

    /**
     * (Tùy chọn) Tùy chỉnh thông báo lỗi tiếng Việt
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.unique'   => 'Email này đã được đăng ký.',
            'password.min'   => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'fullName.required' => 'Vui lòng nhập họ và tên.',
            'fullName.max' => 'Họ và tên không được vượt quá 100 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'dateOfBirth.date' => 'Ngày sinh không đúng định dạng.',
        ];
    }

    /**
     * Ghi đè hàm này để trả về JSON lỗi đúng format {statusCode, message, data}
     */
    protected function failedValidation(Validator $validator)
    {
        // Lấy danh sách lỗi
        $errors = $validator->errors();

        // Ném ra Exception để dừng code ngay lập tức và trả về JSON lỗi
        throw new HttpResponseException(
            $this->error('Dữ liệu không hợp lệ', 422, $errors)
        );
    }
}
