<?php

namespace Database\Seeders;

use App\Enums\ApprovalType;
use App\Enums\BidOfferStatus;
use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderBidStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\RequestType;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\BidOffer;
use App\Models\CustomerAddress;
use App\Models\CustomerApproval;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\Payment;
use App\Models\ProviderBid;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed 6 Providers, 2-3 Technicians per provider, 15 Customers, and 15 complete Bookings.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        // 1. Fetch catalog services created by CategorySeeder
        $allServices = Service::all();

        // 2. Admin Account
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '+18005550000',
            'is_active' => true,
        ]);

        // 3. Create 6 Provider Companies & linked 2-3 Technicians per Provider
        $providerCompanies = [
            ['company_name' => 'Apex Home Services', 'email' => 'provider@example.com', 'address' => '742 Evergreen Terrace, Suite 100, Sector 12', 'lat' => 28.6139, 'lng' => 77.2090, 'radius' => 50.00],
            ['company_name' => 'QuickFix Plumbing Experts', 'email' => 'quickfix@example.com', 'address' => '1088 Ocean Drive, Industrial Park Block B', 'lat' => 28.6200, 'lng' => 77.2150, 'radius' => 40.00],
            ['company_name' => 'Volt Master Electrics', 'email' => 'voltmaster@example.com', 'address' => '42 Wallaby Way, Commercial Tech Hub', 'lat' => 28.6050, 'lng' => 77.2000, 'radius' => 45.00],
            ['company_name' => 'CoolAir HVAC Solutions', 'email' => 'coolair@example.com', 'address' => '221B Baker Street, Central Plaza', 'lat' => 28.6300, 'lng' => 77.2200, 'radius' => 60.00],
            ['company_name' => 'ProClean & Maintenance Co', 'email' => 'proclean@example.com', 'address' => '350 Fifth Avenue, Floor 14', 'lat' => 28.6100, 'lng' => 77.1950, 'radius' => 35.00],
            ['company_name' => 'BuildRight Technical Services', 'email' => 'buildright@example.com', 'address' => '124 Conch Street, Enterprise Zone', 'lat' => 28.6250, 'lng' => 77.2100, 'radius' => 50.00],
        ];

        $createdProviders = collect();
        $createdTechnicians = collect();

        foreach ($providerCompanies as $index => $pData) {
            $pUser = User::firstOrCreate([
                'email' => $pData['email'],
            ], [
                'name' => $pData['company_name'].' Org',
                'password' => Hash::make('password'),
                'role' => UserRole::Provider,
                'phone' => '+1555100'.($index + 1).'00',
                'is_active' => true,
            ]);

            $pProfile = ProviderProfile::firstOrCreate([
                'user_id' => $pUser->id,
            ], [
                'company_name' => $pData['company_name'],
                'business_registration' => 'REG-2026-00'.($index + 1),
                'address' => $pData['address'],
                'service_radius_km' => $pData['radius'],
                'commission' => 10.00,
                'latitude' => $pData['lat'],
                'longitude' => $pData['lng'],
                'is_approved' => true,
                'is_accepting_jobs' => true,
            ]);

            // Offer all services
            foreach ($allServices as $serv) {
                ProviderService::firstOrCreate([
                    'provider_profile_id' => $pProfile->id,
                    'service_id' => $serv->id,
                ], [
                    'custom_price' => $serv->base_price,
                    'is_offered' => true,
                ]);
            }

            $createdProviders->push(['user' => $pUser, 'profile' => $pProfile]);

            // Create 2-3 Technicians under this Provider
            $techCount = ($index % 2 === 0) ? 3 : 2;
            for ($t = 1; $t <= $techCount; $t++) {
                $techUser = User::firstOrCreate([
                    'email' => 'tech_'.($index + 1)."_{$t}@example.com",
                ], [
                    'name' => "Tech {$t} - {$pData['company_name']}",
                    'password' => Hash::make('password'),
                    'role' => UserRole::Technician,
                    'phone' => '+1555900'.($index + 1).$t,
                    'is_active' => true,
                ]);

                $techProfile = TechnicianProfile::firstOrCreate([
                    'user_id' => $techUser->id,
                ], [
                    'provider_id' => $pUser->id,
                    'provider_profile_id' => $pProfile->id,
                    'qualification' => 'Senior Certified Technician',
                    'is_active' => true,
                    'is_approved' => true,
                    'duty_status' => 'on_duty',
                ]);

                $createdTechnicians->push(['user' => $techUser, 'profile' => $techProfile, 'provider' => $pUser]);
            }
        }

        // 4. Create 15 Customer Users & Saved Addresses
        $createdCustomers = collect();
        $customerNames = [
            'John Customer', 'Emma Watson', 'Michael Brown', 'Sophia Davis', 'James Wilson',
            'Olivia Martinez', 'Robert Taylor', 'Ava Anderson', 'David Thomas', 'Isabella Jackson',
            'William White', 'Mia Harris', 'Richard Martin', 'Charlotte Thompson', 'Joseph Garcia',
        ];

        foreach ($customerNames as $i => $name) {
            $cUser = User::firstOrCreate([
                'email' => 'customer_'.($i + 1).'@example.com',
            ], [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'phone' => '+1444'.sprintf('%07d', $i + 1),
                'is_active' => true,
            ]);

            CustomerAddress::firstOrCreate([
                'user_id' => $cUser->id,
                'is_default' => true,
            ], [
                'label' => 'Primary Residence',
                'address_line' => (100 + $i * 12).' Green Park Avenue, Sector '.($i + 1),
                'latitude' => 28.6139 + ($i * 0.002),
                'longitude' => 77.2090 + ($i * 0.003),
            ]);

            $createdCustomers->push($cUser);
        }

        // 5. Seed 15 Service Bookings / Requests across full lifecycle spectrum
        $requestStatuses = [
            ServiceRequestStatus::Submitted,
            ServiceRequestStatus::Matching,
            ServiceRequestStatus::Invited,
            ServiceRequestStatus::InNegotiation,
            ServiceRequestStatus::Awarded,
            ServiceRequestStatus::Assigned,
            ServiceRequestStatus::InProgress,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
            ServiceRequestStatus::Completed,
        ];

        foreach ($requestStatuses as $bIndex => $targetStatus) {
            $customer = $createdCustomers->get($bIndex % $createdCustomers->count());
            $service = $allServices->get($bIndex % $allServices->count());

            $providerData = $createdProviders->get($bIndex % $createdProviders->count());
            $providerUser = $providerData['user'];
            $providerProfile = $providerData['profile'];

            $techData = $createdTechnicians->where('provider.id', $providerUser->id)->first() ?? $createdTechnicians->first();
            $techUser = $techData['user'];

            $createdDate = now()->subDays(15 - $bIndex);

            $reqNumber = sprintf('SR-%s-%06d', $createdDate->format('Ymd'), $bIndex + 1);

            $serviceRequest = ServiceRequest::create([
                'request_number' => $reqNumber,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'request_type' => ($bIndex % 2 === 0) ? RequestType::Broadcast : RequestType::Direct,
                'target_provider_id' => ($bIndex % 2 === 1) ? $providerUser->id : null,
                'description' => 'Urgent service needed for '.$service->name.' at residence.',
                'address_line' => '123 Avenue Road, Unit '.($bIndex + 1),
                'latitude' => 28.6139 + ($bIndex * 0.001),
                'longitude' => 77.2090 + ($bIndex * 0.001),
                'preferred_date' => $createdDate->addDay()->toDateString(),
                'priority' => 'normal',
                'status' => $targetStatus,
                'created_at' => $createdDate,
                'updated_at' => $createdDate,
            ]);

            // Dispatch Invitation
            ProviderJobRequest::create([
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $providerUser->id,
                'provider_profile_id' => $providerProfile->id,
                'status' => ($targetStatus === ServiceRequestStatus::Submitted) ? ProviderJobRequestStatus::Sent : ProviderJobRequestStatus::Selected,
                'sent_at' => $createdDate,
            ]);

            // Bids & Negotiation for requests past Submitted state
            if ($targetStatus !== ServiceRequestStatus::Submitted && $targetStatus !== ServiceRequestStatus::Matching) {
                $bidAmount = $service->base_price + ($bIndex * 15);

                $bid = ProviderBid::create([
                    'service_request_id' => $serviceRequest->id,
                    'provider_id' => $providerUser->id,
                    'amount' => $bidAmount,
                    'estimated_duration' => '2 to 3 Hours',
                    'availability_date' => $createdDate->toDateString(),
                    'message' => 'We can dispatch senior technicians with full tools immediately.',
                    'status' => in_array($targetStatus, [ServiceRequestStatus::Awarded, ServiceRequestStatus::Assigned, ServiceRequestStatus::InProgress, ServiceRequestStatus::Completed])
                        ? ProviderBidStatus::Accepted
                        : ProviderBidStatus::UnderNegotiation,
                    'created_at' => $createdDate,
                ]);

                // Negotiation offers trace
                BidOffer::create([
                    'provider_bid_id' => $bid->id,
                    'sender_type' => 'provider',
                    'sender_id' => $providerUser->id,
                    'amount' => $bidAmount + 50,
                    'message' => 'Initial quote for full service',
                    'status' => BidOfferStatus::Countered,
                ]);

                BidOffer::create([
                    'provider_bid_id' => $bid->id,
                    'sender_type' => 'customer',
                    'sender_id' => $customer->id,
                    'amount' => $bidAmount,
                    'message' => 'Agreed on discounted estimate',
                    'status' => BidOfferStatus::Accepted,
                ]);
            }

            // Technician Assignment for requests past Awarded state
            if (in_array($targetStatus, [ServiceRequestStatus::Assigned, ServiceRequestStatus::InProgress, ServiceRequestStatus::Completed])) {
                $assignment = JobAssignment::create([
                    'service_request_id' => $serviceRequest->id,
                    'provider_id' => $providerUser->id,
                    'technician_id' => $techUser->id,
                    'assigned_at' => $createdDate->addHours(2),
                    'accepted_at' => $createdDate->addHours(3),
                    'arrived_at' => $createdDate->addHours(4),
                    'started_at' => $createdDate->addHours(4),
                    'completed_at' => ($targetStatus === ServiceRequestStatus::Completed) ? $createdDate->addHours(7) : null,
                    'status' => ($targetStatus === ServiceRequestStatus::Completed) ? JobAssignmentStatus::Completed : JobAssignmentStatus::InProgress,
                ]);

                // Field Inspection
                JobInspection::create([
                    'service_request_id' => $serviceRequest->id,
                    'job_assignment_id' => $assignment->id,
                    'technician_id' => $techUser->id,
                    'technical_notes' => 'Site inspected. Materials and labor confirmed.',
                    'estimated_labor_hours' => 2.5,
                    'estimated_material_cost' => 150.00,
                ]);

                // Technical Quote
                $laborSubtotal = 300.00;
                $materialSubtotal = 150.00;
                $tax = round(($laborSubtotal + $materialSubtotal) * 0.05, 2);
                $totalQuote = $laborSubtotal + $materialSubtotal + $tax;

                $quote = Quote::create([
                    'service_request_id' => $serviceRequest->id,
                    'provider_id' => $providerUser->id,
                    'technician_id' => $techUser->id,
                    'labor_subtotal' => $laborSubtotal,
                    'material_subtotal' => $materialSubtotal,
                    'tax' => $tax,
                    'discount' => 0.00,
                    'total' => $totalQuote,
                    'status' => QuoteStatus::Approved,
                ]);

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'description' => 'Technician Labor Hours',
                    'type' => 'labor',
                    'quantity' => 2.5,
                    'unit_price' => 120.00,
                    'total_price' => $laborSubtotal,
                ]);

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'description' => 'Replacement Parts & Materials',
                    'type' => 'material',
                    'quantity' => 1.0,
                    'unit_price' => 150.00,
                    'total_price' => $materialSubtotal,
                ]);

                CustomerApproval::create([
                    'customer_id' => $customer->id,
                    'service_request_id' => $serviceRequest->id,
                    'quote_id' => $quote->id,
                    'approval_type' => ApprovalType::InitialQuote,
                    'status' => 'approved',
                    'approved_at' => $createdDate->addHours(3),
                ]);

                JobProgress::create([
                    'service_request_id' => $serviceRequest->id,
                    'technician_id' => $techUser->id,
                    'progress_percentage' => ($targetStatus === ServiceRequestStatus::Completed) ? 100 : 60,
                    'description' => 'Work executing on-site by certified technician.',
                ]);
            }

            // Invoices, Payments, Earnings, and Reviews for Completed Requests
            if ($targetStatus === ServiceRequestStatus::Completed) {
                $invNumber = sprintf('INV-%s-%06d', $createdDate->format('Ymd'), $bIndex + 1);
                $invTotal = $service->base_price + 100.00;
                $invTax = round($invTotal * 0.05, 2);
                $finalAmount = $invTotal + $invTax;

                $invoice = Invoice::create([
                    'invoice_number' => $invNumber,
                    'service_request_id' => $serviceRequest->id,
                    'customer_id' => $customer->id,
                    'provider_id' => $providerUser->id,
                    'subtotal' => $invTotal,
                    'tax' => $invTax,
                    'discount' => 0.00,
                    'total' => $finalAmount,
                    'status' => InvoiceStatus::Paid,
                    'created_at' => $createdDate->addHours(7),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Service Completion Fee - '.$service->name,
                    'amount' => $invTotal,
                ]);

                Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'payment_gateway' => 'stripe',
                    'transaction_id' => 'TXN_STRIPE_'.Str::random(12),
                    'amount' => $finalAmount,
                    'status' => PaymentStatus::Successful,
                    'paid_at' => $createdDate->addHours(7),
                ]);

                $platformFee = round($finalAmount * 0.10, 2);
                $netProvider = round($finalAmount - $platformFee, 2);

                ProviderEarning::create([
                    'provider_id' => $providerUser->id,
                    'service_request_id' => $serviceRequest->id,
                    'invoice_id' => $invoice->id,
                    'gross_amount' => $finalAmount,
                    'platform_fee' => $platformFee,
                    'net_amount' => $netProvider,
                    'created_at' => $createdDate->addHours(7),
                ]);

                Review::create([
                    'service_request_id' => $serviceRequest->id,
                    'customer_id' => $customer->id,
                    'provider_id' => $providerUser->id,
                    'technician_id' => $techUser->id,
                    'rating' => rand(4, 5),
                    'comment' => 'Outstanding service! The technician arrived on time and fixed everything quickly.',
                    'created_at' => $createdDate->addHours(8),
                ]);
            }
        }
    }
}
