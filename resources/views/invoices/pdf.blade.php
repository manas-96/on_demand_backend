<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.5;
            font-size: 13px;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #eeeeee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .header-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .brand-logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563EB;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #6B7280;
            margin-top: 3px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            margin: 0;
            font-size: 22px;
            color: #111827;
        }
        .badge-paid {
            display: inline-block;
            background-color: #D1FAE5;
            color: #065F46;
            font-weight: bold;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        .section-heading {
            font-size: 12px;
            font-weight: bold;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 4px;
        }
        .info-block p {
            margin: 2px 0;
            font-size: 12px;
        }
        .info-block strong {
            color: #111827;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #F3F4F6;
            color: #374151;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #E5E7EB;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 12px;
        }
        .items-table tr:nth-child(even) {
            background-color: #FAFAFA;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 12px;
        }
        .totals-table tr.grand-total td {
            border-top: 2px solid #2563EB;
            border-bottom: 2px solid #2563EB;
            font-weight: bold;
            font-size: 14px;
            color: #111827;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 11px;
            color: #9CA3AF;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-logo">ON DEMAND</div>
                    <div class="brand-subtitle">Home & On-Demand Service Solutions</div>
                </td>
                <td class="invoice-title">
                    <h2>OFFICIAL INVOICE</h2>
                    <div style="font-size: 12px; color: #4B5563; margin-top: 4px;">
                        <strong>Invoice #:</strong> {{ $invoiceNumber }}<br>
                        <strong>Booking #:</strong> {{ $requestNumber }}<br>
                        <strong>Date Issued:</strong> {{ $dateIssued }}
                    </div>
                    <div class="badge-paid">FULL PAYMENT DONE ✓</div>
                </td>
            </tr>
        </table>

        <!-- Customer & Provider Info -->
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-heading">Billed To (Customer)</div>
                    <div class="info-block">
                        <p><strong>Name:</strong> {{ $customerName }}</p>
                        <p><strong>Email:</strong> {{ $customerEmail }}</p>
                        <p><strong>Phone:</strong> {{ $customerPhone }}</p>
                        <p><strong>Service Location:</strong> {{ $serviceAddress }}</p>
                    </div>
                </td>
                <td>
                    <div class="section-heading">Service Provider Details</div>
                    <div class="info-block">
                        <p><strong>Provider:</strong> {{ $providerName }}</p>
                        <p><strong>Technician:</strong> {{ $technicianName }}</p>
                        <p><strong>Service Title:</strong> {{ $serviceTitle }}</p>
                        <p><strong>Category:</strong> {{ $categoryName }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Itemized Line Items -->
        <div class="section-heading">Itemized Charges</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 15%; text-align: right;">Unit Price</th>
                    <th style="width: 20%; text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item['description'] }}</strong>
                        </td>
                        <td style="text-transform: capitalize; color: #6B7280;">{{ $item['type'] ?? 'Service' }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($item['total_price'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6B7280;">
                            Standard Service Package Charges
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Invoice Totals Breakdown -->
        <table class="totals-table">
            <tr>
                <td>Labor & Parts Subtotal:</td>
                <td style="text-align: right;">${{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($tax > 0)
            <tr>
                <td>Estimated Tax (5%):</td>
                <td style="text-align: right;">${{ number_format($tax, 2) }}</td>
            </tr>
            @endif
            @if($discount > 0)
            <tr>
                <td>Discount / Offer Credit:</td>
                <td style="text-align: right; color: #10B981;">-${{ number_format($discount, 2) }}</td>
            </tr>
            @endif
            @if(isset($tipAmount) && $tipAmount > 0)
            <tr>
                <td style="color: #4F46E5; font-weight: bold;">Technician Tip:</td>
                <td style="text-align: right; color: #4F46E5; font-weight: bold;">+${{ number_format($tipAmount, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Total Invoice Amount:</td>
                <td style="text-align: right;">${{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #059669; font-weight: bold;">Upfront Deposit Paid Credit:</td>
                <td style="text-align: right; color: #059669; font-weight: bold;">-${{ number_format($depositPaid, 2) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Remaining Net Balance Paid:</td>
                <td style="text-align: right; font-weight: bold; color: #2563EB;">$0.00</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for using On Demand Services! For support, email support@ondemand.com or call 1-800-555-ON-DEMAND.</p>
            <p style="font-size: 10px; margin-top: 5px;">This is a computer-generated tax invoice and requires no physical signature.</p>
        </div>
    </div>
</body>
</html>
