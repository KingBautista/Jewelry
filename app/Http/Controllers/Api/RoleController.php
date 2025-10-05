<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use App\Services\MessageService;
use App\Models\Role;
use App\Http\Resources\RoleResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Role Management",
 *     description="Role management endpoints"
 * )
 */
class RoleController extends BaseController
{
	public function __construct(RoleService $roleService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($roleService, $messageService);
  }

  /**
   * @OA\Post(
   *     path="/api/user-management/roles",
   *     summary="Create a new role",
   *     description="Create a new role with permissions",
   *     tags={"Role Management"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name","description"},
   *             @OA\Property(property="name", type="string", example="admin"),
   *             @OA\Property(property="description", type="string", example="Administrator role"),
   *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"), example={1,2,3})
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Role created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="role", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
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

  /**
   * @OA\Put(
   *     path="/api/user-management/roles/{id}",
   *     summary="Update a role",
   *     description="Update an existing role with permissions",
   *     tags={"Role Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name","description"},
   *             @OA\Property(property="name", type="string", example="updated_admin"),
   *             @OA\Property(property="description", type="string", example="Updated administrator role"),
   *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"), example={1,2,3,4})
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="role", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found"
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
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
   * @OA\Get(
   *     path="/api/user-management/roles",
   *     summary="Get all roles",
   *     description="Retrieve a list of all roles with their permissions",
   *     tags={"Role Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Roles retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="roles", type="array", @OA\Items(
   *                 @OA\Property(property="id", type="integer", example=1),
   *                 @OA\Property(property="name", type="string", example="admin"),
   *                 @OA\Property(property="description", type="string", example="Administrator role"),
   *                 @OA\Property(property="permissions", type="array", @OA\Items(type="object"))
   *             ))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
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
