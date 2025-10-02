<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CustomerPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $newPassword;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $newPassword)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Password Reset - Customer Portal')
                    ->view('emails.customer-password-reset')
                    ->with([
                        'user' => $this->user,
                        'newPassword' => $this->newPassword,
                        'loginUrl' => env('CUSTOMER_PORTAL_URL', 'http://localhost:3001') . '/login'
                    ]);
    }
}
