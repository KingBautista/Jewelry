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
            background: #fff;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: #000;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .logo-text {
            line-height: 1;
        }
        
        .logo-subtitle {
            font-size: 8px;
            margin-top: 2px;
        }
        
        .company-info {
            font-size: 12px;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #000;
            text-align: right;
        }
        
        .invoice-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 80px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #666;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
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
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .subtotal-row td {
            text-align: right;
            padding: 8px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .payment-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .payment-details {
            flex: 1;
            margin-right: 30px;
        }
        
        .terms-conditions {
            flex: 1;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        .payment-method {
            margin-bottom: 15px;
        }
        
        .payment-method-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .payment-method-details {
            font-size: 11px;
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
        
        .product-info {
            display: flex;
            margin-bottom: 20px;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-images {
            width: 200px;
            margin-left: 20px;
        }
        
        .product-images img {
            width: 100%;
            max-width: 150px;
            height: auto;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .financial-section {
            padding: 20px;
            background: #fff;
        }
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #000;
        }
        
        .financial-table th,
        .financial-table td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #000;
            font-size: 11px;
        }
        
        .financial-table th {
            background: #000;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        .financial-table td {
            background: #fff;
        }
        
        .total-row {
            background: #000 !important;
            color: white !important;
            font-weight: bold;
            font-size: 12px;
        }
        
        .payment-info {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #000;
        }
        
        .payment-info h4 {
            margin: 0 0 10px 0;
            color: #000;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .footer {
            padding: 15px 20px;
            background: #000;
            color: white;
            text-align: center;
            font-size: 10px;
            border-top: 2px solid #000;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f0f0f0;
            color: #000;
        }
        
        .status-draft { background: #f0f0f0; color: #000; border: 1px solid #000; }
        .status-sent { background: #e6f3ff; color: #000; border: 1px solid #000; }
        .status-paid { background: #e6ffe6; color: #000; border: 1px solid #000; }
        .status-overdue { background: #ffe6e6; color: #000; border: 1px solid #000; }
        .status-cancelled { background: #f0f0f0; color: #000; border: 1px solid #000; }
        
        .payment-status-unpaid { background: #ffe6e6; color: #000; border: 1px solid #000; }
        .payment-status-partially_paid { background: #fff2e6; color: #000; border: 1px solid #000; }
        .payment-status-fully_paid { background: #e6ffe6; color: #000; border: 1px solid #000; }
        .payment-status-overdue { background: #f0f0f0; color: #000; border: 1px solid #000; }
        
        .item-status-pending { background: #f0f0f0; color: #000; border: 1px solid #000; }
        .item-status-packed { background: #e6f3ff; color: #000; border: 1px solid #000; }
        .item-status-for_delivery { background: #e6f3ff; color: #000; border: 1px solid #000; }
        .item-status-delivered { background: #e6ffe6; color: #000; border: 1px solid #000; }
        .item-status-returned { background: #ffe6e6; color: #000; border: 1px solid #000; }
        
        @media print {
            body { margin: 0; padding: 0; }
            .invoice-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <div class="logo">
                    <div class="logo-text">IL LUSSO</div>
                    <div class="logo-subtitle">EST. 2020</div>
                </div>
                <div class="company-info">
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">{{ $invoice->customer_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value">{{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </div>
        
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
                <tr>
                    <td class="description">{{ $invoice->product_name }}</td>
                    <td class="unit-price">{{ number_format($invoice->price, 0, '.', ',') }}</td>
                    <td class="qty">1</td>
                    <td class="total">{{ number_format($invoice->price, 0, '.', ',') }}</td>
                </tr>
                @if($invoice->description)
                <tr>
                    <td class="description">{{ $invoice->description }}</td>
                    <td class="unit-price">-</td>
                    <td class="qty">-</td>
                    <td class="total">-</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="3" style="text-align: right;">Total</td>
                    <td class="total">PhP {{ number_format($invoice->total_amount, 0, '.', ',') }}</td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Payment Details and Terms -->
        <div class="payment-section">
            <div class="payment-details">
                <div class="section-title">Payment Details:</div>
                
                <div class="payment-method">
                    <div class="payment-method-title">BPI Savings Account</div>
                    <div class="payment-method-details">
                        Sarah Nicole Santiago<br>
                        09829191315
                    </div>
                </div>
                
                <div class="payment-method">
                    <div class="payment-method-title">GCash</div>
                    <div class="payment-method-details">
                        Sarah Nicole Santiago<br>
                        09174788238
                    </div>
                </div>
            </div>
            
            <div class="terms-conditions">
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
            </div>
        </div>
        
        @if($invoice->notes)
        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
            <h4 style="margin: 0 0 10px 0; font-size: 12px; font-weight: bold;">Notes</h4>
            <p style="margin: 0; font-size: 11px;">{{ $invoice->notes }}</p>
        </div>
        @endif
    </div>
</body>
</html>
