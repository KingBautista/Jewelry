<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;

class PaymentSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Payment Submission - ' . $this->payment->invoice->invoice_number)
                    ->view('emails.payment-submission-notification')
                    ->with([
                        'payment' => $this->payment,
                        'paymentSubmission' => $this->payment, // Keep for backward compatibility
                        'adminUrl' => env('ADMIN_APP_URL', 'http://localhost:3000')
                    ]);
    }
}
