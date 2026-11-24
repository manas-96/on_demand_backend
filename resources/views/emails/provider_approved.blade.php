@extends('emails.layout')

@section('content')
    <h2 style="color: #1e293b; margin-top: 0;">Your provider account is approved 🎉</h2>

    <p>Hello <strong>{{ $recipient->name }}</strong>,</p>

    <p><strong>{{ $providerProfile->company_name }}</strong> has been approved on Grab A Hand. You will now receive job requests from customers in your service area.</p>

    <div class="card">
        <p style="margin: 4px 0;"><strong>Next steps:</strong></p>
        <p style="margin: 4px 0;">1. Add your technicians in the provider panel.</p>
        <p style="margin: 4px 0;">2. Review the services you offer.</p>
        <p style="margin: 4px 0;">3. Keep "accepting jobs" switched on to receive requests.</p>
    </div>

    <p><a class="btn" href="{{ route('provider.dashboard') }}">Open provider panel</a></p>
@endsection
