@extends('emails.layout')

@section('content')
    <h2 style="color: #16a34a; margin-top: 0;">🎊 Booking Completed & Settled!</h2>

    <p>Hello <strong>{{ $recipient->name }}</strong>,</p>

    <p>Service Request <strong>#{{ $serviceRequest->request_number }}</strong> has been successfully completed and settled.</p>

    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b;">Service Summary</h3>
        <p style="margin: 4px 0;"><strong>Request Number:</strong> #{{ $serviceRequest->request_number }}</p>
        <p style="margin: 4px 0;"><strong>Category / Service:</strong> {{ $serviceRequest->category?->name ?? 'Service' }} ({{ $serviceRequest->service?->name ?? 'General' }})</p>
        <p style="margin: 4px 0;"><strong>Customer:</strong> {{ $serviceRequest->customer?->name }}</p>
        <p style="margin: 4px 0;"><strong>Completed Date:</strong> {{ $assignment->completed_at ? $assignment->completed_at->inDisplayTimezone()->format('M d, Y H:i') : now()->inDisplayTimezone()->format('M d, Y') }}</p>
    </div>

    @if($recipientRole === 'customer')
        <p>Thank you for using Grab A Hand! Please take a moment to leave a review for your technician in the Customer app.</p>
    @elseif($recipientRole === 'provider')
        <p>Your net earnings and invoice financial breakdown for this service request have been processed and reflected in your Provider Dashboard.</p>
    @else
        <p>Great job completing this job assignment! Check your Technician App for your updated job history.</p>
    @endif
@endsection
