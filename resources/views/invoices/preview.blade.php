<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Preview - {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f5f5f5;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 15px 30px;
            background: #000;
            color: white;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: #000;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-start;
            margin-right: 20px;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid white;
            padding: 10px;
            position: relative;
        }
        
        .logo-text {
            line-height: 1;
            font-size: 24px;
            position: absolute;
            bottom: 10px;
            left: 10px;
            font-weight: bold;
        }
        
        .logo-subtitle {
            font-size: 10px;
            margin-top: 5px;
            text-align: center;
            width: 100%;
            color: white;
        }
        
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .company-info {
            font-size: 14px;
            color: white;
        }
        
        .invoice-title {
            font-size: 48px;
            font-weight: bold;
            color: white;
            text-align: right;
        }
        
        .invoice-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 100px;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .items-table {
            width: calc(100% - 100px);
            border-collapse: collapse;
            margin: 30px 50px;
        }
        
        .items-table th {
            background: #000;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .items-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
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
            padding: 12px;
            font-size: 16px;
        }
        
        .payment-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 30px;
        }
        
        .payment-details {
            flex: 0.6;
            margin-right: 40px;
        }
        
        .terms-conditions {
            flex: 1;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 16px;
            color: #000;
            text-transform: uppercase;
        }
        
        .payment-method {
            margin-bottom: 20px;
            padding: 15px 0;
            display: flex;
            align-items: center;
        }
        
        .payment-logo {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .payment-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .payment-content {
            flex: 1;
        }
        
        .payment-method-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #000;
        }
        
        .payment-method-details {
            font-size: 13px;
            line-height: 1.4;
        }
        
        .terms-list {
            font-size: 12px;
            line-height: 1.6;
        }
        
        .terms-list ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .terms-list li {
            margin-bottom: 10px;
        }
        
        .product-info {
            display: flex;
            margin: 20px 0 15px 0;
            padding: 0 30px;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-details .detail-row:first-child {
            margin-top: 15px;
        }
        
        .product-details .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .product-images {
            width: 250px;
            margin-left: 30px;
        }
        
        .product-images img {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        
        .notes-section {
            margin: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .notes-section h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        
        .notes-section p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .footer {
            padding: 20px 30px;
            background: #000;
            color: white;
            text-align: center;
            font-size: 12px;
        }
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #000;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #333;
        }
        
        .btn-secondary {
            background: #666;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        @media print {
            .action-buttons {
                display: none;
            }
            body {
                background: white;
            }
            .container {
                box-shadow: none;
                border-radius: 0;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .invoice-title {
                font-size: 32px;
                margin-top: 20px;
            }
            
            .payment-section {
                flex-direction: column;
            }
            
            .payment-details {
                margin-right: 0;
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <a href="{{ route('invoice.preview', $invoice->id) }}?print=1" class="btn" onclick="window.print(); return false;">Print</a>
        <a href="/api/invoices/{{ $invoice->id }}/pdf" class="btn btn-secondary" target="_blank">Download PDF</a>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <div class="logo-container">
                    <div class="logo">
                        <div class="logo-text">IL<br>LUSSO</div>
                    </div>
                    <div class="logo-subtitle">EST 2020</div>
                </div>
                <div class="company-info">
                </div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </div>
        
        <!-- Customer Information -->
        <div class="product-info">
            <div class="product-details">
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $invoice->customer_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $invoice->issue_date ? $invoice->issue_date->format('M d, Y') : 'N/A' }}</span>
                </div>
            </div>
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
                    <td class="description">{{ $invoice->product_name ?: 'Product/Service' }}</td>
                    <td class="unit-price">₱{{ number_format($invoice->price, 2, '.', ',') }}</td>
                    <td class="qty">1</td>
                    <td class="total">₱{{ number_format($invoice->price, 2, '.', ',') }}</td>
                </tr>
                @if($invoice->description)
                <tr>
                    <td class="description" style="font-style: italic; color: #666;">{{ $invoice->description }}</td>
                    <td class="unit-price">-</td>
                    <td class="qty">-</td>
                    <td class="total">-</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="3" style="text-align: right; font-size: 16px;">TOTAL AMOUNT</td>
                    <td class="total" style="font-size: 18px;">₱{{ number_format($invoice->total_amount, 2, '.', ',') }}</td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Payment Details and Terms -->
        <div class="payment-section">
            <div class="payment-details">
                <div class="section-title">Payment Details</div>
                
                <div class="payment-method">
                    <div class="payment-logo">
                        <img src="{{ asset('assets/img/bpi-logo.png') }}" alt="BPI Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display: none; width: 100%; height: 100%; background: #f0f0f0; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #333;">BPI</div>
                    </div>
                    <div class="payment-content">
                        <div class="payment-method-title">BPI Savings Account</div>
                        <div class="payment-method-details">
                            Sarah Nicole Santiago<br>
                            09829191315
                        </div>
                    </div>
                </div>
                
                <div class="payment-method">
                    <div class="payment-logo">
                        <img src="{{ asset('assets/img/gcash-logo.png') }}" alt="GCash Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display: none; width: 100%; height: 100%; background: #f0f0f0; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #333;">GC</div>
                    </div>
                    <div class="payment-content">
                        <div class="payment-method-title">GCash</div>
                        <div class="payment-method-details">
                            Sarah Nicole Santiago<br>
                            09174788238
                        </div>
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
        
        
        <div class="footer">
            <p>Thank you for your business! | IL LUSSO Jewelry | Established 2020</p>
        </div>
    </div>

    <script>
        // Auto-print if print parameter is in URL
        if (window.location.search.includes('print=1')) {
            window.print();
        }
    </script>
</body>
</html>
