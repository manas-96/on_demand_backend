@extends('emails.layout')

@section('content')
    <h2 style="color: #2563eb; margin-top: 0;">📋 Quotation Ready for Review</h2>

    <p>Hello <strong>{{ $serviceRequest->customer?->name ?? 'Customer' }}</strong>,</p>

    <p>The provider has reviewed and approved the onsite inspection quote for your request <strong>#{{ $serviceRequest->request_number }}</strong>. It is now ready for your review and approval.</p>

    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b;">Quotation Summary</h3>
        @if(is_array($quote) || is_object($quote))
            <p style="margin: 4px 0;"><strong>Labor Subtotal:</strong> ${{ number_format((float)($quote['labor_subtotal'] ?? $quote->labor_subtotal ?? 0), 2) }}</p>
            <p style="margin: 4px 0;"><strong>Material Subtotal:</strong> ${{ number_format((float)($quote['material_subtotal'] ?? $quote->material_subtotal ?? 0), 2) }}</p>
            <p style="margin: 4px 0;"><strong>Tax Amount:</strong> ${{ number_format((float)($quote['tax'] ?? $quote->tax ?? 0), 2) }}</p>
            <div style="font-size: 18px; font-weight: bold; color: #16a34a; margin-top: 10px;">
                Total Quote: ${{ number_format((float)($quote['total'] ?? $quote->total ?? 0), 2) }}
            </div>
        @endif
    </div>

    <p>Please open your Customer app to view the itemized breakdown and approve the quotation so work can commence.</p>
@endsection
