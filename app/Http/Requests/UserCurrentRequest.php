<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserCurrentRequest extends FormRequest
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
        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();
        $profileTable = ($user->role->name === 'CUSTOMER') ? 'customers' : 'staff';
        $userId = $user->user_id;
        return [
            'email'    => 'required|email', 
            'full_name' => 'required|string|max:100',

            'phone' => [
                'nullable',
                'string',
                Rule::unique($profileTable, 'phone')->ignore($userId, 'user_id')
            ],
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date'
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
            'phone.string'    => 'Phone phải là chuỗi ký tự.',
            'address.string'  => 'Address phải là chuỗi ký tự.',
            'date_of_birth.date' => 'Date of birth phải là ngày.',
            'phone.string'    => 'Số điện thoại không hợp lệ.',
            'phone.unique'    => 'Số điện thoại này đã được sử dụng.',
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