<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; 
class LocationRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|max:255|unique:locations,name',
            'slug' => 'nullable|string|unique:locations,slug',
            'description' => 'required|string',
            'image_url' => 'required|string',
            'region' => 'required|in:Northern,Central,Southeast,Southwest',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.max' => 'Name khong duoc vuot qua 255 ky tu.',
            'name.unique' => 'Name nay da ton tai.',
            'slug.required' => 'Vui lòng nhập slug.',
            'slug.unique' => 'Slug nay da ton tai.',
            'description.required' => 'Vui lòng nhập description.',
            'image_url.required' => 'Vui lòng nhập image_url.',
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