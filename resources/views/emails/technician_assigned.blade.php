@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">🧰 New Job Assignment Received!</h2>

    <p>Hello <strong>{{ $technician->name }}</strong>,</p>

    <p>You have been assigned to perform a service request by <strong>{{ $provider?->providerProfile?->company_name ?? $provider?->name ?? 'your service provider' }}</strong>. Please find the job assignment details below:</p>

    <div class="card">
        <div style="font-weight: bold; font-size: 16px; color: #2563eb; margin-bottom: 12px;">
            Request #{{ $serviceRequest->request_number }}
        </div>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'General Service' }}</p>
        @if($serviceRequest->subCategory)
            <p style="margin: 4px 0;"><strong>Subcategory:</strong> {{ $serviceRequest->subCategory->name }}</p>
        @endif
        @if($serviceRequest->service)
            <p style="margin: 4px 0;"><strong>Service:</strong> {{ $serviceRequest->service->name }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Scheduled Date:</strong> {{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'As scheduled' }}</p>
        <p style="margin: 4px 0;"><strong>Customer Location:</strong> {{ $serviceRequest->address_line ?? 'Provided in app' }}</p>
        <p style="margin: 4px 0;"><strong>Job Description:</strong> {{ $serviceRequest->description ?? 'No extra notes' }}</p>
    </div>

    <p>Please open your Field Technician app to view the job location, perform inspection, and update progress.</p>
@endsection
