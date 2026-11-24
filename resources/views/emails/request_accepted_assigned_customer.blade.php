@extends('emails.layout')

@section('content')
    <h2 style="color: #16a34a; margin-top: 0;">✅ Service Request Accepted!</h2>

    <p>Hello <strong>{{ $serviceRequest->customer?->name ?? 'Customer' }}</strong>,</p>

    <p>Great news! Your service request <strong>#{{ $serviceRequest->request_number }}</strong> has been accepted and confirmed.</p>

    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b;">Job Details</h3>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'Service' }}</p>
        <p style="margin: 4px 0;"><strong>Service:</strong> {{ $serviceRequest->service?->name ?? 'On-Demand Service' }}</p>
        @if($provider)
            <p style="margin: 4px 0;"><strong>Provider:</strong> {{ $provider->name }}</p>
        @endif
        @if($technician)
            <p style="margin: 4px 0;"><strong>Assigned Technician:</strong> {{ $technician->name }} ({{ $technician->phone }})</p>
        @endif
        <p style="margin: 4px 0;"><strong>Scheduled Date:</strong> {{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'As scheduled' }}</p>
        <p style="margin: 4px 0;"><strong>Address:</strong> {{ $serviceRequest->address_line }}</p>
    </div>

    <p>You can track the technician's live progress and chat directly inside the Customer app.</p>
@endsection
