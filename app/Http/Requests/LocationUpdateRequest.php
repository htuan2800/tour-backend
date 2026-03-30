<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; 
use Illuminate\Validation\Rule;
class LocationUpdateRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $locationId = $this->route('id');
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('locations', 'name')->ignore($locationId, 'location_id') 
            ],
            'slug' => [
                'nullable',
                'string',
                Rule::unique('locations', 'slug')->ignore($locationId, 'location_id')
            ],
            'description' => 'required|string',
            'region' => 'required|in:Northern,Central,Southeast,Southwest',
            'image_url' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.max' => 'Name khong duoc vuot qua 255 ky tu.',
            'slug.required' => 'Vui lòng nhập slug.',
            'slug.unique' => 'Slug nay da ton tai.',
            'description.required' => 'Vui lòng nhập description.',
            'region.in' => 'Vùng miền không hợp lệ.',
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