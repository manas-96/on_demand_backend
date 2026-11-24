@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">🎉 Booking Request Received!</h2>

    <p>Hello <strong>{{ $customer->name }}</strong>,</p>

    <p>Thank you for booking with Grab A Hand. We have received your service request and are currently connecting you with verified nearby service providers.</p>

    <div class="card">
        <div style="font-weight: bold; font-size: 16px; color: #2563eb; margin-bottom: 12px;">
            Booking Ref #{{ $serviceRequest->request_number }}
        </div>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'General Service' }}</p>
        @if($serviceRequest->subCategory)
            <p style="margin: 4px 0;"><strong>Subcategory:</strong> {{ $serviceRequest->subCategory->name }}</p>
        @endif
        @if($serviceRequest->service)
            <p style="margin: 4px 0;"><strong>Service:</strong> {{ $serviceRequest->service->name }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Scheduled Date:</strong> {{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'As requested' }}</p>
        <p style="margin: 4px 0;"><strong>Service Address:</strong> {{ $serviceRequest->address_line ?? 'Provided in app' }}</p>
        <p style="margin: 4px 0;"><strong>Notes:</strong> {{ $serviceRequest->description ?? 'No specific notes' }}</p>
        <p style="margin: 4px 0;"><strong>Current Status:</strong> <span style="background-color: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: bold;">Submitted</span></p>
    </div>

    <p>You can track the progress of your request and review incoming provider bids or technician details in the Grab A Hand app.</p>
@endsection
