<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class CustomerRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'full_name' => 'required|string|max:100',
            'password' => 'required|string|min:8', 
            'role'     => 'required|exists:roles,name',
            'phone' => 'required|string|unique:customers,phone',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.unique'   => 'Email bị trùng.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.string'   => 'Họ và tên phải là chuỗi ký tự.',
            'full_name.max'      => 'Họ và tên không được vượt quá 100 ký tự.',
            'phone.required' => 'Vui lòng nhập phone.',
            'phone.unique'   => 'Phone bị trùng.',
            'phone.string'    => 'Phone phải là chuỗi ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự.', 
            'role.required'     => 'Vui lòng chọn vai trò người dùng.',
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