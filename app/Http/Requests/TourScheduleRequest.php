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
            'departure_date' => 'required|date|after:today',
            'price_adult'    => 'required|numeric|min:0',
            'price_child'    => 'required|numeric|min:0',
            'max_capacity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'tour_id.required'        => 'Vui lòng nhập tour_id.',
            'tour_id.exists'          => 'Tour không tồn tại.',
            'departure_date.required' => 'Vui lòng nhập ngày khởi hành.',
            'departure_date.date'     => 'ngày khởi hành khong hop le.',
            'departure_date.after' => 'Ngày khởi hành phải là sau hôm nay.',
            'price_adult.required'    => 'Vui lòng nhập giá người lớn.',
            'price_adult.numeric'     => 'Giá người lớn phải là số.',
            'price_adult.min'         => 'Giá người lớn phải lớn hơn hoặc bằng 0.',
            'price_child.required'    => 'Vui lòng nhập giá trẻ em.',
            'price_child.numeric'     => 'Giá trẻ em phải là số.',
            'price_child.min'         => 'Giá trẻ em phải lớn hơn hoặc bằng 0.',
            'max_capacity.required'   => 'Vui lòng nhập sức chứa.',
            'max_capacity.integer'    => 'Sức chứa phải là số nguyên.',
            'max_capacity.min'        => 'Sức chứa phải lớn hơn hoặc bằng 1.',
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
