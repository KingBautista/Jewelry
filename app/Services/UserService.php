<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailSetting;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordEmail;
use App\Mail\VerifyEmail;
use App\Mail\UserWelcomeEmail;
use App\Mail\UserPasswordUpdateEmail;
use App\Helpers\PasswordHelper;

class UserService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new UserResource(new User), new User());
  }
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    $allUsers = $this->getTotalCount();
    $trashedUsers = $this->getTrashedCount();

    return UserResource::collection(User::query()
    ->with('userRole') // Load the role relationship
    ->where('user_role_id', '!=', 1) // Exclude Developer Account
    ->when($trash, function ($query) {
      return $query->onlyTrashed();
    })
    ->when(request('search'), function ($query) {
      return $query->where('user_login', 'LIKE', '%' . request('search') . '%')
                   ->orWhere('user_email', 'LIKE', '%' . request('search') . '%');
    })
    ->when(request('user_role'), function ($query) {
      return $query->whereHas('userRole', function ($q) {
        $q->where('name', request('user_role'));
      });
    })
    ->when(request('user_status'), function ($query) {
      $status = request('user_status');
      if ($status === 'Active') {
        return $query->where('user_status', 1);
      } elseif ($status === 'Inactive') {
        return $query->where('user_status', 0);
      } elseif ($status === 'Pending') {
        return $query->where('user_status', 0)->whereNotNull('user_activation_key');
      } elseif ($status === 'Suspended') {
        return $query->where('user_status', 0)->whereNull('user_activation_key');
      }
    })
    ->when(request('order'), function ($query) {
        return $query->orderBy(request('order'), request('sort'));
    })
    ->when(!request('order'), function ($query) {
      return $query->orderBy('id', 'desc');
    })
    ->paginate($perPage)->withQueryString()
    )->additional(['meta' => ['all' => $allUsers, 'trashed' => $trashedUsers]]);
  }

  /**
  * Store a newly created resource in storage.
  */
  public function storeWithMeta(array $data, array $metaData)
  {
    $user = parent::store($data); // Call the parent method
    if(count($metaData))
      $user->saveUserMeta($metaData);

    // Send welcome email with user information and temporary password
    $this->sendWelcomeEmail($user, $data['user_pass'] ?? null);

    return new UserResource($user);
  }

  /**
  * Update the specified resource in storage.
  */
  public function updateWithMeta(array $data, array $metaData, User $user, $originalPassword = null)
  {
    // Check if password is being updated
    $passwordUpdated = isset($data['user_pass']) && !empty($data['user_pass']);
    
    $user->update($data);
    if(count($metaData))
      $user->saveUserMeta($metaData);

    // Send password update email if password was changed
    if ($passwordUpdated && $originalPassword) {
      $this->sendPasswordUpdateEmail($user, $originalPassword);
    }

    return new UserResource($user);
  }

  /**
  * Bulk restore a soft-deleted user.
  */
  public function bulkChangePassword($ids) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $this->genTempPassword($user);
      }
    }
  }

  public function genTempPassword(User $user) 
	{
		if($user) {
			$salt = $user->user_salt;
			$new_password = PasswordHelper::generateSalt();
			$password = PasswordHelper::generatePassword($salt, $new_password);

			$user->update(['user_pass' => $password]);

			$this->sendForgotPasswordEmail($user, $new_password);
		}
	}

  /**
  * Bulk change user role.
  */
  public function bulkChangeRole($ids, $user_role_id) 
  {
    if(count($ids) > 0) {
      foreach ($ids as $id) {
        $user = User::findOrFail($id);
        $user->update(['user_role_id' => $user_role_id]);
      }
    }
  }

  /**
  * Send verify email.
  */
  public function sendVerifyEmail($user, $user_key)
  {
    $options = array(
      'verify_url'   => env('ADMIN_APP_URL')."/login/activate/".$user_key,
      'password'   => request('user_pass')
    );

    // Configure mail settings from database
    $this->configureMailFromDatabase();

    Mail::to($user->user_email)->send(new VerifyEmail($user, $options));
  }

  /**
  * Send temporary password.
  */
  public function sendForgotPasswordEmail($user, $new_password = '') 
  {
    $user_pass = ($new_password) ? $new_password : request('user_pass');
    $options = array(
      'login_url' => env('ADMIN_APP_URL')."/login",
      'new_password' => $user_pass
    );

    if($user_pass) {
      // Configure mail settings from database
      $this->configureMailFromDatabase();
      
      Mail::to($user->user_email)->send(new ForgotPasswordEmail($user, $options));
    }
  }

  /**
   * Send welcome email to new user
   */
  public function sendWelcomeEmail($user, $password = null)
  {
    try {
      $options = [
        'login_url' => env('ADMIN_APP_URL') . "/login",
        'password' => $password
      ];

      // Configure mail settings from database
      $this->configureMailFromDatabase();

      Mail::to($user->user_email)->send(new UserWelcomeEmail($user, $options));
      
      \Log::info("Welcome email sent to user: {$user->user_email}");
    } catch (\Exception $e) {
      \Log::error("Failed to send welcome email to user {$user->user_email}: " . $e->getMessage());
    }
  }

  /**
   * Send password update email to user
   */
  public function sendPasswordUpdateEmail($user, $newPassword = null)
  {
    try {
      $options = [
        'login_url' => env('ADMIN_APP_URL') . "/login",
        'new_password' => $newPassword
      ];

      // Configure mail settings from database
      $this->configureMailFromDatabase();

      Mail::to($user->user_email)->send(new UserPasswordUpdateEmail($user, $options));
      
      \Log::info("Password update email sent to user: {$user->user_email}");
    } catch (\Exception $e) {
      \Log::error("Failed to send password update email to user {$user->user_email}: " . $e->getMessage());
    }
  }

  /**
   * Configure mail settings from database
   */
  private function configureMailFromDatabase()
  {
    $mailConfig = EmailSetting::getMailConfig();
    
    // Only force log driver if no proper SMTP is configured
    if (env('MAIL_MAILER') === 'sendmail' && !env('MAIL_HOST')) {
      config(['mail.default' => 'log']);
    }
    
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