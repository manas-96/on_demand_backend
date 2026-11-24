@extends('emails.layout')

@section('content')
    <h2 style="color: #ef4444; margin-top: 0;">❌ Booking Request Cancelled</h2>

    <p>Hello <strong>{{ $recipient->name }}</strong>,</p>

    @if($recipientRole === 'customer')
        <p>This is to confirm that your service request <strong>#{{ $serviceRequest->request_number }}</strong> has been cancelled.</p>
    @elseif($recipientRole === 'provider')
        <p>The service request <strong>#{{ $serviceRequest->request_number }}</strong> assigned to your organization has been cancelled.</p>
    @else
        <p>Your job assignment for service request <strong>#{{ $serviceRequest->request_number }}</strong> has been cancelled.</p>
    @endif

    <div class="card" style="border-left: 4px solid #ef4444;">
        <div style="font-weight: bold; font-size: 16px; color: #ef4444; margin-bottom: 12px;">
            Request #{{ $serviceRequest->request_number }}
        </div>
        <p style="margin: 4px 0;"><strong>Category:</strong> {{ $serviceRequest->category?->name ?? 'General Service' }}</p>
        @if($serviceRequest->subCategory)
            <p style="margin: 4px 0;"><strong>Subcategory:</strong> {{ $serviceRequest->subCategory->name }}</p>
        @endif
        @if($serviceRequest->service)
            <p style="margin: 4px 0;"><strong>Service:</strong> {{ $serviceRequest->service->name }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Cancelled By:</strong> {{ ucfirst($cancelledBy) }}</p>
        @if(!empty($reason))
            <p style="margin: 4px 0;"><strong>Reason:</strong> {{ $reason }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Status:</strong> <span style="background-color: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: bold;">Cancelled</span></p>
    </div>

    @if($recipientRole === 'customer')
        <p>If you cancelled by mistake or need further assistance, you can submit a new booking anytime in the Grab A Hand app.</p>
    @else
        <p>Your schedule has been updated. No further action is required for this request.</p>
    @endif
@endsection
