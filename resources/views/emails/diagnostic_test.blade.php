@extends('emails.layout')

@section('content')
    <h2 style="margin-top: 0; color: #1e293b; font-size: 20px;">SMTP Diagnostic Test Successful!</h2>
    
    <p>This automated email confirms that your Grab A Hand backend (<strong>{{ config('app.name') }}</strong>) has successfully established a live connection to your SMTP server and delivered this test message.</p>

    <div class="card">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 15px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">SMTP Connection Parameters</h3>
        
        <div class="info-row">
            <strong>Mailer Transport:</strong>
            <span>{{ $smtpDetails['mailer'] ?? 'smtp' }}</span>
        </div>
        <div class="info-row">
            <strong>SMTP Host:</strong>
            <span>{{ $smtpDetails['host'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <strong>Port & Protocol:</strong>
            <span>{{ $smtpDetails['port'] ?? '465' }} ({{ $smtpDetails['scheme'] ?? 'smtps' }})</span>
        </div>
        <div class="info-row">
            <strong>From Address:</strong>
            <span>{{ $smtpDetails['from_address'] ?? config('mail.from.address') }}</span>
        </div>
        <div class="info-row">
            <strong>Recipient:</strong>
            <span>{{ $recipientEmail }}</span>
        </div>
        <div class="info-row">
            <strong>Dispatch Mode:</strong>
            <span>{{ strtoupper($smtpDetails['queue_connection'] ?? 'sync') }}</span>
        </div>
        <div class="info-row">
            <strong>Timestamp:</strong>
            <span>{{ $sentAt }}</span>
        </div>
    </div>

    <p style="font-size: 13px; color: #64748b;">If you received this message in your inbox, your email notification service is fully functional and ready to deliver real-time user notifications.</p>
@endsection
