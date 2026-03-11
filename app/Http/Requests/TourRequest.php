<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse; 
class TourRequest extends FormRequest
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
            'image_url' => 'required|string',
            'duration_days' => 'required|integer',
            'duration_nights' => 'required|integer',
            'transportation' => 'required|string',
            'itineraries' => 'required|array|min:1',
            'itineraries.*.day_number' => 'required|integer',
            'itineraries.*.title' => 'required|string|max:255',
            'itineraries.*.description' => 'required|string',
            'destination_ids'   => 'required|array|min:1', // Bắt buộc phải là mảng và chọn ít nhất 1 điểm
            'destination_ids.*' => 'integer|exists:locations,location_id', // Từng item trong mảng phải tồn tại trong Location
            'depart_id' => 'required|integer|exists:locations,location_id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.max' => 'Name khong duoc vuot qua 255 ky tu.',
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