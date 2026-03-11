<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;

class TourScheduleRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_id'        => 'required|exists:tours,tour_id',
            'departure_date' => 'required|date|after_or_equal:today',
            'price_adult'    => 'required|numeric|min:0',
            'price_child'    => 'required|numeric|min:0',
            'max_capacity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'tour_id.required'        => 'Vui lòng nhập tour_id.',
            'tour_id.exists'          => 'Tour khong ton tai.',
            'departure_date.required' => 'Vui lòng nhập departure_date.',
            'departure_date.date'     => 'departure_date khong hop le.',
            'departure_date.after_or_equal' => 'departure_date phai lon hon hoac bang ngay hien tai.',
            'price_adult.required'    => 'Vuiện nhập price_adult.',
            'price_adult.numeric'     => 'price_adult phai la so.',
            'price_adult.min'         => 'price_adult phai lon hon hoac bang 0.',
            'price_child.required'    => 'Vuiện nhập price_child.',
            'price_child.numeric'     => 'price_child phai la so.',
            'price_child.min'         => 'price_child phai lon hon hoac bang 0.',
            'max_capacity.required'   => 'Vuiện nhập max_capacity.',
            'max_capacity.integer'    => 'max_capacity phai la so nguyen.',
            'max_capacity.min'        => 'max_capacity phai lon hon hoac bang 1.',
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
