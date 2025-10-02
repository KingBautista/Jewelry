<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Status Update - Customer Notification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #D4AF37 0%, #F7E7CE 50%, #FFF8DC 100%);
            padding: 30px;
            text-align: center;
            color: #2C2C2C;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .status-box {
            background: #f8f9fa;
            border: 2px solid #D4AF37;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .status-approved {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .payment-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #212529;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37 0%, #F7E7CE 50%, #FFF8DC 100%);
            color: #2C2C2C;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .rejection-reason {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if($paymentSubmission->status === 'approved')
                    ✅ Payment Approved
                @else
                    ❌ Payment Rejected
                @endif
            </h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Invoice #{{ $paymentSubmission->invoice->invoice_number }}</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $paymentSubmission->customer->user_login }},</h2>
            
            <div class="status-box {{ $paymentSubmission->status === 'approved' ? 'status-approved' : 'status-rejected' }}">
                <div class="status-icon">
                    @if($paymentSubmission->status === 'approved')
                        ✅
                    @else
                        ❌
                    @endif
                </div>
                <h3 style="margin: 0;">
                    @if($paymentSubmission->status === 'approved')
                        Payment Approved!
                    @else
                        Payment Rejected
                    @endif
                </h3>
                <p style="margin: 10px 0 0 0;">
                    @if($paymentSubmission->status === 'approved')
                        Your payment has been successfully approved and processed.
                    @else
                        Your payment submission has been rejected. Please see the reason below.
                    @endif
                </p>
            </div>
            
            <div class="payment-details">
                <h3 style="margin-top: 0; color: #D4AF37;">Payment Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Invoice Number:</span>
                    <span class="detail-value">{{ $paymentSubmission->invoice->invoice_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">${{ number_format($paymentSubmission->amount_paid, 2) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Reference Number:</span>
                    <span class="detail-value">{{ $paymentSubmission->reference_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Submitted At:</span>
                    <span class="detail-value">{{ $paymentSubmission->submitted_at->format('M d, Y H:i A') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Reviewed At:</span>
                    <span class="detail-value">{{ $paymentSubmission->reviewed_at->format('M d, Y H:i A') }}</span>
                </div>
            </div>
            
            @if($paymentSubmission->status === 'rejected' && $paymentSubmission->rejection_reason)
                <div class="rejection-reason">
                    <h4 style="margin-top: 0;">Rejection Reason:</h4>
                    <p style="margin: 0;">{{ $paymentSubmission->rejection_reason }}</p>
                </div>
            @endif
            
            @if($paymentSubmission->status === 'approved')
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Your payment has been applied to the invoice</li>
                    <li>The invoice balance has been updated</li>
                    <li>You can view the updated invoice in your customer portal</li>
                    <li>You will receive a receipt confirmation</li>
                </ul>
            @else
                <p><strong>What you can do:</strong></p>
                <ul>
                    <li>Review the rejection reason above</li>
                    <li>Correct any issues and resubmit your payment</li>
                    <li>Contact our support team if you have questions</li>
                    <li>Ensure all payment details are accurate</li>
                </ul>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ $customerPortalUrl }}/invoices/{{ $paymentSubmission->invoice->id }}" class="button">
                    View Invoice Details
                </a>
            </div>
            
            <p>If you have any questions about this payment or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>
            <strong>Jewelry Management Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This email was sent from the Jewelry Management System Customer Portal.</p>
            <p>Please do not reply to this email. For support, contact our customer service team.</p>
        </div>
    </div>
</body>
</html>
