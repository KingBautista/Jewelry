<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SignupRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Helpers\PasswordHelper;
use App\Helpers\AuditTrailHelper;
use App\Models\User;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Mail\ForgotPasswordEmail;
use Illuminate\Support\Facades\Auth; 
use App\Http\Resources\AuthResource;

use Hash;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Jewelry API",
 *     version="1.0.0",
 *     description="API for Jewelry Management System"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
	public function signup(SignupRequest $request) 
	{
		$data = $request->validated();

		$salt = PasswordHelper::generateSalt();
		$password = PasswordHelper::generatePassword($salt, $request->password);
		$activation_key = PasswordHelper::generateSalt();

		$user = User::create([
			'user_login' => $data['username'],
			'user_email' => $data['email'],
			'user_salt' => $salt,
			'user_pass' => $password,
			'user_activation_key' => $activation_key,
		]);

		$user_key = $user->user_activation_key;

		// email sending code here
		$options = array(
			'verify_url'   => env('ADMIN_APP_URL')."/login/activate/".$user_key
		);

		$message = '';
		
		// Configure mail settings from database
		$this->configureMailFromDatabase();
		
		if(Mail::to($user->user_email)->send(new VerifyEmail($user, $options))) {
			$message = 'Aww yeah, you have successfuly registered. Verification email has been sent to your registered email.';
		}

		// Log user registration
		AuditTrailHelper::log(
			'USER_MANAGEMENT',
			'CREATE',
			['user_id' => $user->id, 'user_email' => $user->user_email],
			$user->id
		);

		return response(compact('message'));
		
	}

	/**
	 * Activate registered user.
	 */
	public function activateUser(Request $request) 
	{
		$message = '';

		$user = User::where('user_activation_key', $request->activation_key)
		->where('user_status', 0)->first();
		
		if($user) {
			$user->update(['user_status' => 1]);
			$message = 'Your registered email address has been validated, you can login you account and enjoy.';
		}

		return response(compact('message'));

	}

	/**
	 * Generate a temporary password.
	 */
	public function genTempPassword(ForgotPasswordRequest $request) 
	{
		$data = $request->validated();
		$message = '';

		$user = User::where('user_email', $data['email'])->first();

		if($user) {
			$salt = $user->user_salt;
			$new_password = PasswordHelper::generateSalt();
			$password = PasswordHelper::generatePassword($salt, $new_password);

			$user->update(['user_pass' => $password]);

			$options = array(
				'login_url' => env('ADMIN_APP_URL')."/login",
				'new_password' => $new_password
			);
	
			// Configure mail settings from database
			//$this->configureMailFromDatabase();
			
			if(Mail::to($user->user_email)->send(new ForgotPasswordEmail($user, $options))) {
				$message = 'Your temporary password has been sent to your registered email.';
			}
		}

		return response(compact('message'));
	}

	/**
	 * @OA\Post(
	 *     path="/api/login",
	 *     summary="Login user",
	 *     description="Authenticate user and return access token",
	 *     tags={"Authentication"},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"email","password"},
	 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
	 *             @OA\Property(property="password", type="string", format="password", example="password123")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Login successful",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="user", type="object"),
	 *             @OA\Property(property="token", type="string", example="1|abcdef123456...")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Invalid credentials"
	 *     )
	 * )
	 */
	public function login(LoginRequest $request) 
	{
		$credentials = $request->validated();

		$user = User::where('user_email', $credentials['email'])->where('user_status', 1)->first();
		
		if (!$user || !Hash::check($user->user_salt.$credentials['password'].(env("PEPPER_HASH") ?: ''), $user->user_pass)) {
			// Log failed login attempt
			AuditTrailHelper::log(
				'USER_MANAGEMENT',
				'LOGIN_FAILED',
				['email' => $credentials['email'], 'ip_address' => $request->ip()],
				null
			);
			
			return response([
				'errors' => ['Invalid email or password.'],
				'status' => false,
				'status_code' => 422,
			], 422);
		}
		
		$user->tokens()->delete();

		Auth::login($user);
		$token = $user->createToken('admin')->plainTextToken;
		$user = new AuthResource($user);

		// Log successful login
		AuditTrailHelper::log(
			'USER_MANAGEMENT',
			'LOGIN',
			['user_id' => $user->id, 'user_email' => $user->user_email],
			$user->id
		);

		return response(compact('user', 'token'));	

	}

	/**
	 * @OA\Post(
	 *     path="/api/logout",
	 *     summary="Logout user",
	 *     description="Invalidate the current access token",
	 *     tags={"Authentication"},
	 *     security={{"sanctum":{}}},
	 *     @OA\Response(
	 *         response=204,
	 *         description="Logout successful"
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated"
	 *     )
	 * )
	 */
	public function logout(Request $request) 
	{
		$user = $request->user();
		
		// Log logout before deleting token
		AuditTrailHelper::log(
			'USER_MANAGEMENT',
			'LOGOUT',
			['user_id' => $user->id, 'user_email' => $user->user_email],
			$user->id
		);
		
		$user->currentAccessToken()->delete();
		
		return response('', 204);
	}

	/**
	 * Validate password for the currently authenticated user.
	 */
	public function validatePassword(Request $request)
	{
		$request->validate([
			'password' => 'required|string',
		]);
		$user = $request->user();
		if (!$user) {
			return response(['message' => 'Unauthenticated.'], 401);
		}
		// Use the same password check as login
		if (!\Hash::check($user->user_salt.$request->password.env('PEPPER_HASH'), $user->user_pass)) {
			return response(['message' => 'Invalid password.'], 422);
		}
		
		return response(['message' => 'Password is valid.'], 200);
	}

	/**
	 * Configure mail settings from database
	 */
	private function configureMailFromDatabase()
	{
		$mailConfig = EmailSetting::getMailConfig();
		
		// Force log driver for testing (since sendmail doesn't work on Windows)
		config(['mail.default' => 'log']);
		
		// Set mail configuration dynamically
		config([
			'mail.from.address' => $mailConfig['from']['address'],
			'mail.from.name' => $mailConfig['from']['name'],
		]);
		
		// Set reply-to if configured
		if ($mailConfig['reply_to']['address']) {
			config([
				'mail.reply_to.address' => $mailConfig['reply_to']['address'],
				'mail.reply_to.name' => $mailConfig['reply_to']['name'],
			]);
		}
	}
}
