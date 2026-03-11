<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\ApiResponse;
use Illuminate\Validation\Rule;
class RoleRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Lấy ID của role đang được update từ URL (ví dụ: PUT /api/roles/{role})
        // Nếu là tạo mới (POST) thì biến này sẽ là null
        $roleId = $this->route('id');

        return [
            'name' => [
                'required',
                'max:255',
                // Ràng buộc duy nhất trên bảng roles cột name, ngoại trừ ID hiện tại đang sửa
                Rule::unique('roles', 'name')->ignore($roleId, 'role_id'),
            ],
            'display_name' => 'required|string',

            // Cấp quyền có thể mảng rỗng (nếu tạo role chưa muốn cấp quyền) nên dùng nullable hoặc present
            'permission_ids'   => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,permission_id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.max' => 'Name khong duoc vuot qua 255 ky tu.',
            'name.unique' => 'Mã vai trò (name) này đã tồn tại trên hệ thống, vui lòng chọn tên khác.',
            'display_name.required' => 'Vui lòng nhập display_name.',
            'display_name.string' => 'Display_name phải là một chuỗi.',
            'permission_ids.array' => 'Permission_ids phải là một mảng.',
            'permission_ids.*.integer' => 'Mỗi permission_id phải là một số nguyên.',
            'permission_ids.*.exists' => 'Một hoặc nhiều permission_id không tồn tại trong bảng permissions.',
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
