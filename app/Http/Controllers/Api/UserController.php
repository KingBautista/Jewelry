<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ProfileRequest;
use App\Helpers\PasswordHelper;
use App\Services\UserService;
use App\Services\MessageService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="User management endpoints"
 * )
 */
class UserController extends BaseController
{
	public function __construct(UserService $userService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($userService, $messageService);
  }

  /**
   * @OA\Get(
   *     path="/api/user-management/users",
   *     summary="Get all users",
   *     description="Retrieve a paginated list of all users",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of items per page",
   *         @OA\Schema(type="integer", example=10)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Users retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
   *             @OA\Property(property="current_page", type="integer", example=1),
   *             @OA\Property(property="per_page", type="integer", example=10),
   *             @OA\Property(property="total", type="integer", example=100)
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function index()
  {
    return parent::index();
  }

  /**
   * @OA\Get(
   *     path="/api/user-management/users/{id}",
   *     summary="Get a specific user",
   *     description="Retrieve detailed information about a specific user",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="user", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function show($id)
  {
    return parent::show($id);
  }

  /**
   * @OA\Delete(
   *     path="/api/user-management/users/{id}",
   *     summary="Delete a user",
   *     description="Move a user to trash (soft delete)",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function destroy($id)
  {
    return parent::destroy($id);
  }

  /**
   * @OA\Post(
   *     path="/api/user-management/users",
   *     summary="Create a new user",
   *     description="Create a new user account with role assignment",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"user_login","user_email","user_pass","first_name","last_name"},
   *             @OA\Property(property="user_login", type="string", example="john_doe"),
   *             @OA\Property(property="user_email", type="string", format="email", example="john@example.com"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="password123"),
   *             @OA\Property(property="first_name", type="string", example="John"),
   *             @OA\Property(property="last_name", type="string", example="Doe"),
   *             @OA\Property(property="phone", type="string", example="+1234567890"),
   *             @OA\Property(property="user_role", type="object",
   *                 @OA\Property(property="id", type="integer", example=1)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="User created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User created successfully"),
   *             @OA\Property(property="user", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
  public function store(StoreUserRequest $request)
  {
    // try {
      $data = $request->validated();

      $salt = PasswordHelper::generateSalt();
      $password = PasswordHelper::generatePassword($salt, $data['user_pass']);
      $activation_key = PasswordHelper::generateSalt();

      $userData = [
        'user_login' => $data['user_login'],
        'user_email' => $data['user_email'],
        'user_salt' => $salt,
        'user_pass' => $password,
        'user_status' => 1,
        'user_activation_key' => $activation_key,
      ];

      // Handle user_role_id if provided
      if (isset($data['user_role']['id'])) {
        $userData['user_role_id'] = $data['user_role']['id'];
      }

      $meta_details = [];
      if(isset($request->first_name))
        $meta_details['first_name'] = $request->first_name;
        
      if(isset($request->last_name))
        $meta_details['last_name'] = $request->last_name;

      $user = $this->service->storeWithMeta($userData, $meta_details);
      
      return response($user, 201);
    // } catch (\Exception $e) {
    //   return $this->messageService->responseError();
    // }
  }

  /**
   * @OA\Put(
   *     path="/api/user-management/users/{id}",
   *     summary="Update a user",
   *     description="Update an existing user account with role assignment",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"user_login","user_email"},
   *             @OA\Property(property="user_login", type="string", example="updated_john_doe"),
   *             @OA\Property(property="user_email", type="string", format="email", example="updated@example.com"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="newpassword123"),
   *             @OA\Property(property="first_name", type="string", example="Updated John"),
   *             @OA\Property(property="last_name", type="string", example="Updated Doe"),
   *             @OA\Property(property="phone", type="string", example="+1234567890"),
   *             @OA\Property(property="user_status", type="integer", example=1),
   *             @OA\Property(property="user_role", type="object",
   *                 @OA\Property(property="id", type="integer", example=2)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="user", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found"
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
  public function update(UpdateUserRequest $request, Int $id)
  {
    try {
      $data = $request->validated();
      $user = User::findOrFail($id);
      $oldData = $user->toArray();

      $upData = [
        'user_login' => $request->user_login,
        'user_email' => $request->user_email,
        'user_status' => $request->user_status,
      ];

      // Handle user_role_id if provided
      if (isset($data['user_role']['id'])) {
        $upData['user_role_id'] = $data['user_role']['id'];
      }

      // Handle password update if provided
      $originalPassword = null;
      if (isset($data['user_pass']) && !empty($data['user_pass'])) {
        $salt = $user->user_salt;
        $upData['user_pass'] = PasswordHelper::generatePassword($salt, request('user_pass'));
        $originalPassword = request('user_pass'); // Keep original password for email
      }

      $meta_details = [];
      if(isset($request->first_name))
        $meta_details['first_name'] = $request->first_name;
        
      if(isset($request->last_name))
        $meta_details['last_name'] = $request->last_name;

      $user = $this->service->updateWithMeta($upData, $meta_details, $user, $originalPassword);

      return response($user, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Post(
   *     path="/api/user-management/users/bulk-change-password",
   *     summary="Bulk change user passwords",
   *     description="Change passwords for multiple users at once",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1,2,3})
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Passwords changed successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Passwords have been changed successfully.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
  public function bulkChangePassword(Request $request) 
  {
    try {
      $userIds = $request->ids;
      $newPassword = PasswordHelper::generateSalt(); // Generate a random password
      $count = count($userIds);

      foreach ($userIds as $userId) {
        $user = User::find($userId);
        if ($user) {
          $salt = $user->user_salt;
          $user->user_pass = PasswordHelper::generatePassword($salt, $newPassword);
          $user->save();
        }
      }

      return response(['message' => 'Passwords have been changed successfully.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Post(
   *     path="/api/user-management/users/bulk-change-role",
   *     summary="Bulk change user roles",
   *     description="Change roles for multiple users at once",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids","role"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1,2,3}),
   *             @OA\Property(property="role", type="integer", example=2, description="New role ID")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Roles changed successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Roles have been changed successfully.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
  public function bulkChangeRole(Request $request) 
  {
    try {
      $userIds = $request->ids;
      $roleId = $request->role;
      $count = count($userIds);

      User::whereIn('id', $userIds)->update(['user_role_id' => $roleId]);

      return response(['message' => 'Roles have been changed successfully.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Put(
   *     path="/api/user-management/profile",
   *     summary="Update user profile",
   *     description="Update the authenticated user's profile information",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"user_login","user_email"},
   *             @OA\Property(property="user_login", type="string", example="updated_username"),
   *             @OA\Property(property="user_email", type="string", format="email", example="updated@example.com"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="newpassword123"),
   *             @OA\Property(property="first_name", type="string", example="Updated First Name"),
   *             @OA\Property(property="last_name", type="string", example="Updated Last Name")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Profile updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Profile has been updated successfully."),
   *             @OA\Property(property="user", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     )
   * )
   */
  public function updateProfile(ProfileRequest $request) 
  {
    try {
      $data = $request->validated();
      $user = Auth::user();
      $oldData = $user->toArray();

      $upData = [
        'user_login' => $request->user_login,
        'user_email' => $request->user_email,
      ];

      if (isset($data['user_pass'])) {
        $salt = $user->user_salt;
        $upData['user_pass'] = PasswordHelper::generatePassword($salt, request('user_pass'));
      }

      $meta_details = [];
      if(isset($request->first_name))
        $meta_details['first_name'] = $request->first_name;
        
      if(isset($request->last_name))
        $meta_details['last_name'] = $request->last_name;

      if(isset($request->nickname))
        $meta_details['nickname'] = $request->nickname;

      if(isset($request->biography))
        $meta_details['biography'] = $request->biography;

      if(isset($request->theme))
        $meta_details['theme'] = $request->theme;

      $user = $this->service->updateWithMeta($upData, $meta_details, $user);

      return response([
        'message' => 'Profile has been updated successfully.',
        'user' => $user
      ], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Get(
   *     path="/api/user-management/user",
   *     summary="Get current user",
   *     description="Retrieve the authenticated user's information",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="User information retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="id", type="integer", example=1),
   *             @OA\Property(property="user_login", type="string", example="john_doe"),
   *             @OA\Property(property="user_email", type="string", example="john@example.com"),
   *             @OA\Property(property="user_status", type="integer", example=1),
   *             @OA\Property(property="user_role", type="object"),
   *             @OA\Property(property="user_details", type="object"),
   *             @OA\Property(property="created_at", type="string", format="date-time"),
   *             @OA\Property(property="updated_at", type="string", format="date-time")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function getUser(Request $request) 
  {
    try {
      $user = Auth::user();
      $userData = [
        'id' => $user->id,
        'user_login' => $user->user_login,
        'user_email' => $user->user_email,
        'user_status' => $user->user_status,
        'user_role' => $user->userRole,
        'user_details' => $user->user_details,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
      ];

      return response($userData, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Get(
   *     path="/api/options/users",
   *     summary="Get users for dropdown",
   *     description="Retrieve a list of active users for dropdown/select options",
   *     tags={"User Management"},
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Users retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="users", type="array", @OA\Items(
   *                 @OA\Property(property="id", type="integer", example=1),
   *                 @OA\Property(property="user_login", type="string", example="john_doe"),
   *                 @OA\Property(property="user_email", type="string", example="john@example.com")
   *             ))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function getUsersForDropdown()
  {
    try {
      $users = User::select('id', 'user_login', 'user_email')
        ->where('user_status', 1)
        ->orderBy('user_login')
        ->get();
      
      return response()->json($users);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Failed to fetch users'], 500);
    }
  }
}
