<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function getAllFunction()
    {
        $functions = $this->roleService->getAllFunction();
        return $this->success($functions, 'Danh sách chức năng', 200);
    }

    public function getPagenatedRole(Request $request)
    {
        $limit = $request->query('limit', 10);
        $search = $request->query('search', null);
        $paginator = $this->roleService->getPagenatedRole($limit, $search);
        return $this->success(
            [
                'data' => $paginator->items(),
                'meta' => [
                    'totalItems'   => $paginator->total(),       
                    'itemCount'    => count($paginator->items()),      
                    'itemsPerPage' => $paginator->perPage(),     
                    'totalPages'   => $paginator->lastPage(),   
                    'currentPage'  => $paginator->currentPage(), 
                ],
            ],
            'Danh sách vai trò',
            200
        ) ;
    }

    public function getRoleForStaff()
    {
        $roles = $this->roleService->getRoleForStaff();
        return $this->success($roles, 'Danh sách vai trò cho nhân viên', 200);
    }

    public function create(RoleRequest $request)
    {
        $role = $this->roleService->createRole($request->validated());
        return $this->success($role, 'Tạo vai trò thành công', 201);
    }

    public function getRoleById(string $id)
    {
        $role = $this->roleService->findRoleById($id);
        return $this->success($role, 'Thông tin vai trò', 200);
    }

    public function update(string $id, RoleRequest $request)
    {
        $role = $this->roleService->updateRole($id, $request->validated());
        return $this->success($role, 'Cập nhật vai trò thành công', 200);
    }

    public function delete(string $id)
    {   
        $role=$this->roleService->findRoleById($id);
        $this->roleService->deleteRole($role);
        return $this->success($role, 'Xóa vai trò thành công', 200);
    }

}
