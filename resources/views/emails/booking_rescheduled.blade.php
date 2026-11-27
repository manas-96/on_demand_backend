@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">📅 Booking Rescheduled</h2>

    <p>Hello <strong>{{ $recipient->name }}</strong>,</p>

    <p>
        The customer has moved the date and time of
        {{ $recipientRole === 'technician' ? 'a job assigned to you' : 'a booking with your company' }}.
        Please update your schedule.
    </p>

    <div class="card">
        <div style="font-weight: bold; font-size: 16px; color: #2563eb; margin-bottom: 12px;">
            Request #{{ $serviceRequest->request_number }}
        </div>
        <p style="margin: 4px 0;"><strong>New Schedule:</strong> {{ $newSchedule }}</p>
        <p style="margin: 4px 0; color: #64748b;"><strong>Previously:</strong> <span style="text-decoration: line-through;">{{ $previousSchedule }}</span></p>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'General Service' }}</p>
        @if($serviceRequest->service)
            <p style="margin: 4px 0;"><strong>Service:</strong> {{ $serviceRequest->service->name }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Customer Location:</strong> {{ $serviceRequest->address_line ?? 'Provided in app' }}</p>
    </div>

    <p>
        {{ $recipientRole === 'technician'
            ? 'Open your Field Technician app to see the updated job.'
            : 'Open your provider panel to see the updated booking.' }}
    </p>
@endsection
