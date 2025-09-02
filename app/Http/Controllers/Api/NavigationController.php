<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\NavigationService;
use App\Services\MessageService;

class NavigationController extends BaseController
{
	public function __construct(NavigationService $navigationService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($navigationService, $messageService);
  }

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

  public function getSubNavigations(Int $id)
  {
    try {
      $subNavigations = $this->service->getSubNavigations($id);
      return response($subNavigations, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

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
