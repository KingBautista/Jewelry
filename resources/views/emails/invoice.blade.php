<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f8f9fa;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .content {
            padding: 30px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .invoice-summary h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .summary-label {
            font-weight: bold;
            color: #666;
        }
        
        .summary-value {
            color: #333;
        }
        
        .total-row {
            border-top: 2px solid #667eea;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft { background: #6c757d; color: white; }
        .status-sent { background: #17a2b8; color: white; }
        .status-paid { background: #28a745; color: white; }
        .status-overdue { background: #dc3545; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        
        .payment-status-unpaid { background: #dc3545; color: white; }
        .payment-status-partially_paid { background: #ffc107; color: black; }
        .payment-status-fully_paid { background: #28a745; color: white; }
        .payment-status-overdue { background: #6c757d; color: white; }
        
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 0 10px;
        }
        
        .cta-button:hover {
            background: #5a6fd8;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        .attachment-notice {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #004085;
        }
        
        .attachment-notice strong {
            color: #002752;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
            <p>Jewelry Business Management System</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
                <p>Dear {{ $customerName }},</p>
                <p>Thank you for your business! Please find attached your invoice for the jewelry purchase.</p>
            </div>
            
            <!-- Invoice Summary -->
            <div class="invoice-summary">
                <h3>Invoice Summary</h3>
                <div class="summary-row">
                    <span class="summary-label">Invoice Number:</span>
                    <span class="summary-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Product:</span>
                    <span class="summary-value">{{ $invoice->product_name }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Issue Date:</span>
                    <span class="summary-value">{{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                @if($invoice->due_date)
                <div class="summary-row">
                    <span class="summary-label">Due Date:</span>
                    <span class="summary-value">{{ $invoice->due_date->format('M d, Y') }}</span>
                </div>
                @endif
                <div class="summary-row">
                    <span class="summary-label">Status:</span>
                    <span class="summary-value">
                        <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status_text }}</span>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Payment Status:</span>
                    <span class="summary-value">
                        <span class="status-badge payment-status-{{ $invoice->payment_status }}">{{ $invoice->payment_status_text }}</span>
                    </span>
                </div>
                <div class="summary-row total-row">
                    <span class="summary-label">Total Amount:</span>
                    <span class="summary-value">{{ $invoice->formatted_total_amount }}</span>
                </div>
            </div>
            
            <!-- Attachment Notice -->
            <div class="attachment-notice">
                <strong>📎 PDF Invoice Attached</strong><br>
                A detailed PDF version of your invoice is attached to this email for your records.
            </div>
            
            <!-- Payment Information -->
            @if($invoice->payment_status !== 'unpaid')
            <div class="invoice-summary">
                <h3>Payment Information</h3>
                <div class="summary-row">
                    <span class="summary-label">Total Paid:</span>
                    <span class="summary-value">{{ $invoice->formatted_total_paid_amount }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Remaining Balance:</span>
                    <span class="summary-value">{{ $invoice->formatted_remaining_balance }}</span>
                </div>
                @if($invoice->next_payment_due_date)
                <div class="summary-row">
                    <span class="summary-label">Next Payment Due:</span>
                    <span class="summary-value">{{ $invoice->next_payment_due_date->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
            @endif
            
            <!-- Call to Action -->
            <div class="cta-section">
                <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
                <p>Thank you for choosing our jewelry services!</p>
            </div>
            
            @if($invoice->notes)
            <div class="invoice-summary">
                <h3>Additional Notes</h3>
                <p>{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Jewelry Business Management System</strong></p>
            <p>This is an automated invoice. Please keep this email for your records.</p>
            <p>Generated on {{ now()->format('M d, Y \a\t H:i A') }}</p>
        </div>
    </div>
</body>
</html>
