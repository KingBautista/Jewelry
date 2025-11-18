<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentSubmission;

class PaymentSubmissionStatusUpdate extends Mailable
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
        $subject = $this->paymentSubmission->status === 'approved' 
            ? 'Payment Approved - ' . $this->paymentSubmission->invoice->invoice_number
            : 'Payment Rejected - ' . $this->paymentSubmission->invoice->invoice_number;

        return $this->subject($subject)
                    ->view('emails.payment-submission-status-update')
                    ->with([
                        'paymentSubmission' => $this->paymentSubmission,
                        'customerPortalUrl' => env('CUSTOMER_PORTAL_URL', 'https://customer.illussso.com/')
                    ]);
    }
}
