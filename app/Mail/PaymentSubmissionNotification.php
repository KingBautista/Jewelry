<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentSubmission;

class PaymentSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $paymentSubmission;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PaymentSubmission $paymentSubmission)
    {
        $this->paymentSubmission = $paymentSubmission;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Payment Submission - ' . $this->paymentSubmission->invoice->invoice_number)
                    ->view('emails.payment-submission-notification')
                    ->with([
                        'paymentSubmission' => $this->paymentSubmission,
                        'adminUrl' => env('ADMIN_APP_URL', 'http://localhost:3000')
                    ]);
    }
}
