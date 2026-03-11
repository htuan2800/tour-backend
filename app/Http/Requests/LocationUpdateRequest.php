<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; 
class LocationUpdateRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|max:255',
            'description' => 'required|string',
            'region' => 'required|in:Northern,Central,Southern',
            'image_url' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.max' => 'Name khong duoc vuot qua 255 ky tu.',
            'description.required' => 'Vui lòng nhập description.',
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