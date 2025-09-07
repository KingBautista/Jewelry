<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
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

  public function store(RoleRequest $request)
  {
    try {
      $data = $request->validated();
      $role = $this->service->store($data);
      
      return response($role, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  public function update(RoleRequest $request, Int $id)
  {
    try {
      $data = $request->validated();
      $role = Role::with('rolePermissions')->findOrFail($id);
      $oldData = $role->toArray();

      $role = $this->service->update($data, $id);

      return response($role, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get all roles resource.
   */
  public function getRoles() 
  {
    try {
      return $this->service->getRoles();
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
