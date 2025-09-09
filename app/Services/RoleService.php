<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;

class RoleService extends BaseService
{
  public function __construct()
  {
    // Pass the UserResource class to the parent constructor
    parent::__construct(new RoleResource(new Role), new Role());
  }

  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allRoles = $this->getTotalCount();
      $trashedRoles = $this->getTrashedCount();

      $query = Role::query();
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where('name', 'LIKE', '%' . request('search') . '%');
      }

      // Apply active status filter
      if (request('active')) {
        $active = request('active');
        if ($active === 'Active') {
          $query->where('active', 1);
        } elseif ($active === 'Inactive') {
          $query->where('active', 0);
        }
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
      } else {
        $query->orderBy('id', 'desc');
      }

      return RoleResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allRoles, 'trashed' => $trashedRoles]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch roles: ' . $e->getMessage());
    }
  }

  public function getRoles() 
  {
    return Role::query()->select('id', 'name', 'name as  label')->where('active', 1)->orderBy('id', 'asc')->get()->makeHidden(['permissions']);
  }

  public function store(array $data)
  {
    // Handle permissions separately
    $permissions = $data['permissions'] ?? [];
    unset($data['permissions']);
    
    // Create the role
    $role = $this->model->create($data);
    
    // Add permissions if provided
    if (!empty($permissions)) {
      $this->updateRolePermissions($role, $permissions);
    }
    
    return $this->resource::make($role);
  }

  public function update(array $data, int $id)
  {
    $role = $this->model::findOrFail($id);
    
    // Handle permissions separately
    $permissions = $data['permissions'] ?? [];
    unset($data['permissions']);
    
    // Update the role basic data
    $role->update($data);
    
    // Update permissions if provided
    if (!empty($permissions)) {
      $this->updateRolePermissions($role, $permissions);
    }
    
    return $this->resource::make($role);
  }

  private function updateRolePermissions(Role $role, array $permissions)
  {
    // Delete existing permissions
    $role->rolePermissions()->delete();
    
    // Insert new permissions
    $permissionData = [];
    foreach ($permissions as $parentId => $navigationPermissions) {
      foreach ($navigationPermissions as $navigationId => $permissionTypes) {
        foreach ($permissionTypes as $permissionType => $allowed) {
          if ($allowed) {
            // Get permission ID by name
            $permission = Permission::where('name', $permissionType)->first();
            if ($permission) {
              $permissionData[] = [
                'role_id' => $role->id,
                'navigation_id' => $navigationId,
                'permission_id' => $permission->id,
                'allowed' => 1,
                'created_at' => now(),
                'updated_at' => now(),
              ];
            }
          }
        }
      }
    }
    
    if (!empty($permissionData)) {
      $role->rolePermissions()->insert($permissionData);
    }
  }
}