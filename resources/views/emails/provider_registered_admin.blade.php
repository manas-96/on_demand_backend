@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">New provider awaiting approval</h2>

    <p>Hello <strong>{{ $recipient->name }}</strong>,</p>

    <p>A service provider has registered and verified their email. Please review the profile and approve it before they can receive jobs.</p>

    <div class="card">
        <div style="font-weight: bold; font-size: 16px; color: #2563eb; margin-bottom: 12px;">{{ $providerProfile->company_name }}</div>
        <p style="margin: 4px 0;"><strong>Contact:</strong> {{ $providerProfile->user?->name }} ({{ $providerProfile->user?->email }})</p>
        @if($providerProfile->user?->phone)
            <p style="margin: 4px 0;"><strong>Phone:</strong> {{ $providerProfile->user->phone }}</p>
        @endif
        @if($providerProfile->business_registration)
            <p style="margin: 4px 0;"><strong>Business Registration:</strong> {{ $providerProfile->business_registration }}</p>
        @endif
        <p style="margin: 4px 0;"><strong>Address:</strong> {{ $providerProfile->address }}</p>
        <p style="margin: 4px 0;"><strong>Service Radius:</strong> {{ $providerProfile->service_radius_km }} km</p>
        <p style="margin: 4px 0;"><strong>Registered:</strong> {{ $providerProfile->created_at?->inDisplayTimezone()->format('M d, Y H:i') }}</p>
    </div>

    <p><a class="btn" href="{{ route('admin.providers.show', $providerProfile->id) }}">Review provider</a></p>
@endsection
