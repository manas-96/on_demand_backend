@extends('emails.layout')

@section('content')
    <h2 style="color: #16a34a; margin-top: 0;">🎉 Customer Accepted Quotation!</h2>

    <p>Hello <strong>{{ $provider->name }}</strong>,</p>

    <p>The customer <strong>{{ $serviceRequest->customer?->name ?? 'Customer' }}</strong> has approved and accepted the quotation for request <strong>#{{ $serviceRequest->request_number }}</strong>.</p>

    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b;">Accepted Quotation Summary</h3>
        <p style="margin: 4px 0;"><strong>Request Number:</strong> #{{ $serviceRequest->request_number }}</p>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'Service' }}</p>
        @if(is_array($quote) || is_object($quote))
            <p style="margin: 4px 0; font-size: 16px; font-weight: bold; color: #16a34a;">
                Approved Amount: ${{ number_format((float)($quote['total'] ?? $quote->total ?? 0), 2) }}
            </p>
        @endif
    </div>

    <p>Your assigned technician can now proceed with executing the service work on site.</p>
@endsection
