<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\NavigationService;
use App\Services\MessageService;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Navigation",
 *     description="Navigation management endpoints"
 * )
 */
class NavigationController extends BaseController
{
	public function __construct(NavigationService $navigationService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($navigationService, $messageService);
  }

  /**
   * @OA\Post(
   *     path="/api/navigation-management/navigations",
   *     summary="Create a new navigation item",
   *     description="Create a new navigation menu item",
   *     tags={"Navigation"},
   *     security={{"sanctum":{}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name","url"},
   *             @OA\Property(property="name", type="string", example="Dashboard"),
   *             @OA\Property(property="url", type="string", example="/dashboard"),
   *             @OA\Property(property="icon", type="string", example="fas fa-home"),
   *             @OA\Property(property="parent_id", type="integer", example=null),
   *             @OA\Property(property="order", type="integer", example=1),
   *             @OA\Property(property="is_active", type="boolean", example=true)
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Navigation item created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="navigation", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function store(Request $request)
  {
    try {
      $data = $request->all();
      $resource = $this->service->store($data);
      
      return response($resource, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Put(
   *     path="/api/navigation-management/navigations/{id}",
   *     summary="Update a navigation item",
   *     description="Update an existing navigation menu item",
   *     tags={"Navigation"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name","url"},
   *             @OA\Property(property="name", type="string", example="Updated Dashboard"),
   *             @OA\Property(property="url", type="string", example="/updated-dashboard"),
   *             @OA\Property(property="icon", type="string", example="fas fa-chart-line"),
   *             @OA\Property(property="parent_id", type="integer", example=null),
   *             @OA\Property(property="order", type="integer", example=2),
   *             @OA\Property(property="is_active", type="boolean", example=true)
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation item updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="navigation", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation item not found"
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function update(Request $request, Int $id)
  {
    try {
      $data = $request->all();
      $resource = $this->service->show($id);
      $oldData = $resource->toArray();

      $resource = $this->service->update($data, $resource);

      return response($resource, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Get(
   *     path="/api/navigation-management/navigations/{id}/sub-navigations",
   *     summary="Get sub-navigations",
   *     description="Retrieve sub-navigation items for a parent navigation",
   *     tags={"Navigation"},
   *     security={{"sanctum":{}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Parent Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Sub-navigations retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="sub_navigations", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation item not found"
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function getSubNavigations(Int $id)
  {
    try {
      $subNavigations = $this->service->getSubNavigations($id);
      return response($subNavigations, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * @OA\Get(
   *     path="/api/navigation-management/routes",
   *     summary="Get navigation routes",
   *     description="Retrieve all available navigation routes",
   *     tags={"Navigation"},
   *     security={{"sanctum":{}}},
   *     @OA\Response(
   *         response=200,
   *         description="Routes retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="routes", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated"
   *     )
   * )
   */
  public function getRoutes()
  {
    try {
      $routes = $this->service->getRoutes();
      return response($routes, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
