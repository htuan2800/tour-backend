<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class UserUpdateRequest extends FormRequest
{
    use ApiResponse;

    protected function prepareForValidation()
    {
        if ($this->date_of_birth) {
        $this->merge([
            'date_of_birth' => Carbon::parse($this->date_of_birth)->format('Y-m-d'),
        ]);
    }
    }

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email', 
            'full_name' => 'required|string|max:100',
            'password' => 'nullable|string|min:8', 
            'role'     => 'required|exists:roles,name',

            //staff/customer
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',

            //staff
            // 'salary' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.string'   => 'Họ và tên phải là chuỗi ký tự.',
            'full_name.max'      => 'Họ và tên không được vượt quá 100 ký tự.',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự.', 
            'role.required'     => 'Vui lòng chọn vai trò người dùng.',

            //staff/customer
            'phone.string'    => 'Phone phải là chuỗi ký tự.',
            'address.string'  => 'Address phải là chuỗi ký tự.',
            'date_of_birth.date' => 'Date of birth phải là ngày.',
            
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