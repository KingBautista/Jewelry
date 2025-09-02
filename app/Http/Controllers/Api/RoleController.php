<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use App\Services\MessageService;
use App\Models\Role;
use App\Http\Resources\RoleResource;

class RoleController extends BaseController
{
	public function __construct(RoleService $roleService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($roleService, $messageService);
  }

  public function store(StoreRoleRequest $request)
  {
    try {
      $data = $request->validated();
      $role = $this->service->store($data);
      
      return response($role, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  public function update(UpdateRoleRequest $request, Int $id)
  {
    try {
      $data = $request->validated();
      $role = Role::findOrFail($id);
      $oldData = $role->toArray();

      $role = $this->service->update($data, $role);

      return response($role, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
