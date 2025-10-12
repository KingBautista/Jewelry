<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Payment Submission - Admin Notification</title>
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
        .payment-details {
            background: #f8f9fa;
            border: 2px solid #D4AF37;
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
        .urgent {
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
            <h1>💰 New Payment Submission</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Requires Admin Review</p>
        </div>
        
        <div class="content">
            <h2>Payment Submission Alert</h2>
            
            <p>A customer has submitted a new payment that requires your review and approval.</p>
            
            <div class="payment-details">
                <h3 style="margin-top: 0; color: #D4AF37;">Payment Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Invoice Number:</span>
                    <span class="detail-value">{{ $payment->invoice->invoice_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $payment->customer->user_login }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">₱{{ number_format($payment->amount_paid, 2) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Expected Amount:</span>
                    <span class="detail-value">₱{{ number_format($payment->expected_amount, 2) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Reference Number:</span>
                    <span class="detail-value">{{ $payment->reference_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Submitted At:</span>
                    <span class="detail-value">{{ $payment->created_at->format('M d, Y H:i A') }}</span>
                </div>
                
                @if($payment->receipt_images)
                    <div class="detail-row">
                        <span class="detail-label">Receipt Images:</span>
                        <span class="detail-value">{{ count($payment->receipt_images) }} file(s)</span>
                    </div>
                @endif
            </div>
            
            <div class="urgent">
                <strong>⚠️ Action Required:</strong> This payment submission is pending your review. Please log in to the admin panel to approve or reject this payment.
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $adminUrl }}/payment-management/payments/{{ $payment->id }}" class="button">Review Payment Submission</a>
            </div>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Log in to the admin panel</li>
                <li>Navigate to Payment Management</li>
                <li>Review the payment details and receipt images</li>
                <li>Approve or reject the payment</li>
                <li>The customer will receive an email notification of your decision</li>
            </ul>
            
            <p>Best regards,<br>
            <strong>Jewelry Management System</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the Jewelry Management System.</p>
            <p>Please review this payment submission as soon as possible.</p>
        </div>
    </div>
</body>
</html>
