@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">🛠️ New Service Request Available!</h2>

    <p>Hello <strong>{{ $provider->name }}</strong>,</p>

    <p>A new customer service request matching your service area has just been posted. Check the details below and submit your bid or accept the request:</p>

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
        <p style="margin: 4px 0;"><strong>Preferred Date:</strong> {{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'As soon as possible' }}</p>
        <p style="margin: 4px 0;"><strong>Location / Address:</strong> {{ $serviceRequest->address_line ?? 'Provided in app' }}</p>
        <p style="margin: 4px 0;"><strong>Description:</strong> {{ $serviceRequest->description ?? 'No extra notes' }}</p>
    </div>

    <p>Open your Provider app dashboard to respond and claim this job.</p>
@endsection
