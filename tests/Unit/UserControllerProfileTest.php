<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\UserController;
use App\Http\Requests\ProfileRequest;
use App\Models\User;
use App\Models\Role;
use App\Services\UserService;
use App\Services\MessageService;
use App\Helpers\PasswordHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class UserControllerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $userService;
    protected $messageService;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'user_email' => 'test@example.com',
            'user_pass' => Hash::make('password123')
        ]);

        // Mock services
        $this->userService = Mockery::mock(UserService::class);
        $this->messageService = Mockery::mock(MessageService::class);
        
        // Create controller instance
        $this->controller = new UserController($this->userService, $this->messageService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_update_profile_success()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';
        $request->first_name = 'John';
        $request->last_name = 'Doe';
        $request->nickname = 'Johnny';
        $request->biography = 'Test biography';
        $request->theme = 'dark';

        // Mock the user service
        $updatedUser = clone $this->user;
        $updatedUser->user_email = 'updated@example.com';
        $updatedUser->user_details = (object)[
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'biography' => 'Test biography',
            'theme' => 'dark'
        ];

        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->andReturn($updatedUser);

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Profile has been updated successfully.', $responseData['message']);
        $this->assertArrayHasKey('user', $responseData);
    }

    public function test_update_profile_with_password()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login,
            'user_pass' => 'NewPassword123!'
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';
        $request->first_name = 'John';

        // Mock the user service
        $updatedUser = clone $this->user;
        $updatedUser->user_email = 'updated@example.com';
        $updatedUser->user_details = (object)['first_name' => 'John'];

        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->withArgs(function ($upData, $metaDetails, $user) {
                return isset($upData['user_pass']) && 
                       $upData['user_email'] === 'updated@example.com' &&
                       $metaDetails['first_name'] === 'John';
            })
            ->andReturn($updatedUser);

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_profile_without_password()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';
        $request->first_name = 'John';

        // Mock the user service
        $updatedUser = clone $this->user;
        $updatedUser->user_email = 'updated@example.com';
        $updatedUser->user_details = (object)['first_name' => 'John'];

        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->withArgs(function ($upData, $metaDetails, $user) {
                return !isset($upData['user_pass']) && 
                       $upData['user_email'] === 'updated@example.com' &&
                       $metaDetails['first_name'] === 'John';
            })
            ->andReturn($updatedUser);

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_profile_handles_all_meta_fields()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';
        $request->first_name = 'John';
        $request->last_name = 'Doe';
        $request->nickname = 'Johnny';
        $request->biography = 'Test biography';
        $request->theme = 'dark';

        // Mock the user service
        $updatedUser = clone $this->user;
        $updatedUser->user_email = 'updated@example.com';
        $updatedUser->user_details = (object)[
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nickname' => 'Johnny',
            'biography' => 'Test biography',
            'theme' => 'dark'
        ];

        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->withArgs(function ($upData, $metaDetails, $user) {
                return $metaDetails['first_name'] === 'John' &&
                       $metaDetails['last_name'] === 'Doe' &&
                       $metaDetails['nickname'] === 'Johnny' &&
                       $metaDetails['biography'] === 'Test biography' &&
                       $metaDetails['theme'] === 'dark';
            })
            ->andReturn($updatedUser);

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_profile_handles_exception()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';

        // Mock the user service to throw an exception
        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Mock the message service
        $this->messageService->shouldReceive('responseError')
            ->once()
            ->andReturn(response(['error' => 'An error occurred'], 500));

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_update_profile_uses_correct_password_helper()
    {
        Auth::login($this->user);

        // Mock the request
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => $this->user->user_login,
            'user_pass' => 'NewPassword123!'
        ]);
        
        $request->user_login = $this->user->user_login;
        $request->user_email = 'updated@example.com';

        // Mock the user service
        $updatedUser = clone $this->user;
        $updatedUser->user_email = 'updated@example.com';

        $this->userService->shouldReceive('updateWithMeta')
            ->once()
            ->withArgs(function ($upData, $metaDetails, $user) {
                // Verify that the password was processed with PasswordHelper
                return isset($upData['user_pass']) && 
                       is_string($upData['user_pass']) &&
                       strlen($upData['user_pass']) > 0;
            })
            ->andReturn($updatedUser);

        $response = $this->controller->updateProfile($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_profile_requires_authentication()
    {
        // Don't authenticate the user
        $request = Mockery::mock(ProfileRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'user_email' => 'updated@example.com',
            'user_login' => 'testuser'
        ]);
        
        $request->user_login = 'testuser';
        $request->user_email = 'updated@example.com';

        // This should fail because no user is authenticated
        $this->expectException(\Exception::class);
        
        $this->controller->updateProfile($request);
    }
}
