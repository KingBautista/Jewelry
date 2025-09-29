<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Updated Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background: #fff;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .header-table {
            width: 100%;
            background: #000;
            color: white;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .header-table td {
            padding: 4px 0;
            vertical-align: bottom;
        }
        
        .header-table td:first-child {
            padding-left: 10px;
        }
        
        .logo-cell {
            width: 100px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: #000;
            color: white;
            border: 2px solid white;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            padding: 5px;
            position: relative;
        }
        
        .logo-text {
            font-size: 18px;
            line-height: 1;
            position: absolute;
            bottom: 5px;
            left: 5px;
        }
        
        .logo-subtitle {
            font-size: 8px;
            margin-top: 5px;
            text-align: center;
            color: white;
        }
        
        .invoice-title {
            font-size: 48px;
            font-weight: bold;
            color: white;
            text-align: right;
            padding-right: 15px;
        }
        
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .customer-table td {
            padding: 8px 0;
            border: none;
        }
        
        .detail-label {
            font-weight: bold;
            width: 100px;
            color: #666;
        }
        
        .detail-value {
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .items-table th {
            background: #000;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .items-table .description {
            text-align: left;
        }
        
        .items-table .unit-price {
            text-align: center;
        }
        
        .items-table .qty {
            text-align: center;
        }
        
        .items-table .total {
            text-align: right;
        }
        
        .subtotal-row {
            background: #f8f8f8;
            font-weight: bold;
        }
        
        .subtotal-row td {
            text-align: right;
            padding: 8px;
            font-size: 12px;
        }
        
        .payment-history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .payment-history-table th {
            background: #f8f9fa;
            color: #333;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #ddd;
        }
        
        .payment-history-table td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        
        .payment-schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .payment-schedule-table th {
            background: #e9ecef;
            color: #333;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #ddd;
        }
        
        .payment-schedule-table td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        
        .summary-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .payment-table td {
            vertical-align: top;
            padding: 0 10px;
        }
        
        .payment-details-cell {
            width: 40%;
        }
        
        .terms-cell {
            width: 60%;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 12px;
            color: #000;
            text-transform: uppercase;
        }
        
        .payment-method-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .payment-method-table td {
            padding: 5px 0;
            border: none;
            vertical-align: middle;
        }
        
        .payment-logo-cell {
            width: 40px;
            text-align: center;
        }
        
        .payment-logo {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border-radius: 3px;
            display: inline-block;
            text-align: center;
            line-height: 30px;
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }
        
        .payment-content-cell {
            padding-left: 10px;
        }
        
        .payment-method-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .payment-method-details {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .terms-list {
            font-size: 10px;
            line-height: 1.4;
        }
        
        .terms-list ol {
            margin: 0;
            padding-left: 15px;
        }
        
        .terms-list li {
            margin-bottom: 8px;
        }
        
        .footer-table {
            width: 100%;
            background: #000;
            color: white;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .footer-table td {
            padding: 15px;
            text-align: center;
            font-size: 10px;
        }
        
        @media print {
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <table>
                    <tr>
                        <td>
                            <div class="logo">
                                <div class="logo-text">IL<br>LUSSO</div>
                            </div>
                            <div class="logo-subtitle">EST 2020</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 60%;"></td>
            <td style="text-align: right;">
                <div class="invoice-title">UPDATED INVOICE</div>
            </td>
        </tr>
    </table>
    
    <!-- Customer Information -->
    <table class="customer-table">
        <tr>
            <td class="detail-label">Customer:</td>
            <td class="detail-value">{{ $invoice->customer_name }}</td>
        </tr>
        <tr>
            <td class="detail-label">Date:</td>
            <td class="detail-value">{{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : 'N/A' }}</td>
        </tr>
    </table>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="description">DESCRIPTION</th>
                <th class="unit-price">UNIT PRICE</th>
                <th class="qty">QTY</th>
                <th class="total">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @if($invoice->items && count($invoice->items) > 0)
                @foreach($invoice->items as $item)
                <tr>
                    <td class="description">{{ $item->product_name ?: 'Product/Service' }}</td>
                    <td class="unit-price">P{{ number_format($item->price, 2, '.', ',') }}</td>
                    <td class="qty">1</td>
                    <td class="total">P{{ number_format($item->price, 2, '.', ',') }}</td>
                </tr>
                @if($item->description)
                <tr>
                    <td class="description" style="font-style: italic; color: #666;">{{ $item->description }}</td>
                    <td class="unit-price">-</td>
                    <td class="qty">-</td>
                    <td class="total">-</td>
                </tr>
                @endif
                @endforeach
            @else
                <!-- Fallback for old single product format -->
                <tr>
                    <td class="description">{{ $invoice->product_name ?: 'Product/Service' }}</td>
                    <td class="unit-price">P{{ number_format($invoice->price, 2, '.', ',') }}</td>
                    <td class="qty">1</td>
                    <td class="total">P{{ number_format($invoice->price, 2, '.', ',') }}</td>
                </tr>
                @if($invoice->description)
                <tr>
                    <td class="description" style="font-style: italic; color: #666;">{{ $invoice->description }}</td>
                    <td class="unit-price">-</td>
                    <td class="qty">-</td>
                    <td class="total">-</td>
                </tr>
                @endif
            @endif
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="3" style="text-align: right; font-size: 12px;">TOTAL AMOUNT</td>
                <td class="total" style="font-size: 14px;">P{{ number_format($invoice->total_amount, 2, '.', ',') }}</td>
            </tr>
        </tfoot>
    </table>
    
    
    <!-- Paid Payment Schedules Section -->
    @if($paidSchedules && count($paidSchedules) > 0)
    <div class="section-title">Paid Payment Schedule</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="description">DESCRIPTION</th>
                <th class="unit-price">DUE DATE</th>
                <th class="qty">STATUS</th>
                <th class="total">AMOUNT PAID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paidSchedules as $schedule)
            <tr>
                <td class="description">{{ $schedule->payment_type }} - Payment #{{ $schedule->payment_order }}</td>
                <td class="unit-price">{{ \Carbon\Carbon::parse($schedule->due_date)->format('M d, Y') }}</td>
                <td class="qty">
                    <span style="color: green; font-weight: bold;">PAID</span>
                </td>
                <td class="total">P{{ number_format($schedule->paid_amount, 2, '.', ',') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <!-- Payment Summary -->
    <div class="summary-section">
        <div class="section-title">Payment Summary</div>
        <div class="summary-row">
            <span>Total Invoice Amount:</span>
            <span>P{{ number_format($invoice->total_amount, 2, '.', ',') }}</span>
        </div>
        <div class="summary-row">
            <span>Total Paid Amount:</span>
            <span>P{{ number_format($totalPaid, 2, '.', ',') }}</span>
        </div>
        <div class="summary-row total">
            <span>Remaining Balance:</span>
            <span>P{{ number_format($remainingBalance, 2, '.', ',') }}</span>
        </div>
    </div>
    
    <!-- Receipt Images Section -->
    @if(isset($receiptImages) && count($receiptImages) > 0)
    <div class="section-title">Payment Receipt</div>
    <div style="margin: 20px 0;">
        @foreach($receiptImages as $index => $receiptImage)
        <div style="margin-bottom: 20px; text-align: center; page-break-inside: avoid;">
            <img src="{{ $receiptImage }}" alt="Payment Receipt {{ $index + 1 }}" 
                 style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 5px; display: block; margin: 0 auto;" />
            <div style="margin-top: 8px; font-size: 11px; color: #666; font-weight: bold;">
                Payment Receipt {{ $index + 1 }}
            </div>
        </div>
        @endforeach
    </div>
    @endif
    
    <!-- Payment Details and Terms -->
    <table class="payment-table">
        <tr>
            <td class="payment-details-cell">
                <div class="section-title">Payment Details</div>
                
                <table class="payment-method-table">
                    <tr>
                        <td class="payment-logo-cell">
                            <div class="payment-logo">
                                @php
                                $bpiImagePath = public_path('assets/img/bpi-logo.png');
                                $bpiBase64 = '';
                                if (file_exists($bpiImagePath)) {
                                    $bpiBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($bpiImagePath));
                                }
                                @endphp
                                @if($bpiBase64)
                                <img src="{{ $bpiBase64 }}" alt="BPI" style="width: 100%; height: 100%; object-fit: contain;">
                                @else
                                <div style="width: 100%; height: 100%; background: #f0f0f0; text-align: center; line-height: 30px; font-size: 10px; font-weight: bold; color: #333; border: 1px solid #ddd;">BPI</div>
                                @endif
                            </div>
                        </td>
                        <td class="payment-content-cell">
                            <div class="payment-method-title">BPI Savings Account</div>
                            <div class="payment-method-details">
                                Sarah Nicole Santiago<br>
                                09829191315
                            </div>
                        </td>
                    </tr>
                </table>
                
                <table class="payment-method-table">
                    <tr>
                        <td class="payment-logo-cell">
                            <div class="payment-logo">
                                @php
                                $gcashImagePath = public_path('assets/img/gcash-logo.png');
                                $gcashBase64 = '';
                                if (file_exists($gcashImagePath)) {
                                    $gcashBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($gcashImagePath));
                                }
                                @endphp
                                @if($gcashBase64)
                                <img src="{{ $gcashBase64 }}" alt="GCash" style="width: 100%; height: 100%; object-fit: contain;">
                                @else
                                <div style="width: 100%; height: 100%; background: #f0f0f0; text-align: center; line-height: 30px; font-size: 10px; font-weight: bold; color: #333; border: 1px solid #ddd;">GC</div>
                                @endif
                            </div>
                        </td>
                        <td class="payment-content-cell">
                            <div class="payment-method-title">GCash</div>
                            <div class="payment-method-details">
                                Sarah Nicole Santiago<br>
                                09174788238
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="terms-cell">
                <div class="section-title">Terms & Conditions</div>
                <div class="terms-list">
                    <ol>
                        <li><strong>All Sales Are Final</strong> - Orders are considered final and non-cancellable once confirmed. No returns, exchanges, or cancellations will be accepted.</li>
                        <li><strong>Insured Shipping</strong> - All items are shipped fully insured via the client's preferred courier, unless otherwise arranged. Risk of loss transfers upon delivery to the courier.</li>
                        <li><strong>Authenticity Guaranteed</strong> - All products are guaranteed authentic. Items will be accompanied by the appropriate certifications, documentation, and/or provenance, where applicable.</li>
                        <li><strong>Payment Terms</strong> - Full payment is required prior to shipment. Accepted payment methods include the BPI and GCash Accounts indicated on the left. Prices are exclusive of shipping and applicable taxes unless stated otherwise.</li>
                        <li><strong>Delivery Timelines</strong> - Estimated delivery timelines are provided for reference and may vary depending on courier service and destination. We are not liable for delays caused by third-party logistics providers.</li>
                        <li><strong>Condition Disclosure</strong> - All items are carefully inspected and described as accurately as possible. For pre-owned items, minor signs of wear may be present and are considered part of the item's condition.</li>
                    </ol>
                </div>
            </td>
        </tr>
    </table>
    
    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td>Thank you for your business! | IL LUSSO Jewelry | Established 2020</td>
        </tr>
    </table>
</body>
</html>
