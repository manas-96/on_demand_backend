<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_fee',
        'tax_rate',
        'minimum_booking_charge',
        'privacy_policy',
    ];

    protected function casts(): array
    {
        return [
            'platform_fee' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'minimum_booking_charge' => 'decimal:2',
        ];
    }

    /**
     * Get default generated privacy policy markdown content.
     */
    public static function getDefaultPrivacyPolicy(): string
    {
        return <<<'MARKDOWN'
# Privacy Policy

**Last Updated:** September 2026

Welcome to Grab A Hand Service Marketplace ("we", "our", or "us"). We are committed to respecting and protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website, mobile applications, or utilize our home and field service platform.

---

## 1. Information We Collect

We collect information necessary to provide, manage, and optimize our Grab A Hand service marketplace:

* **Account & Profile Data:** Name, email address, phone number, physical address, and account role (Customer, Provider Company, or Field Technician).
* **Location Data:** Precise GPS coordinates collected during active service dispatch, site visits, and job execution to match local service providers and field technicians.
* **Service Request Details:** Service categories, problem descriptions, scope notes, and attached problem photographs uploaded by customers.
* **Financial & Billing Information:** Transaction history, invoices, payment receipts, platform commission fees, and provider payout ledgers processed securely through certified payment gateways.
* **Communications & Feedback:** Ratings, reviews, and messages exchanged between customers, provider companies, and technicians during service fulfillment.

---

## 2. How We Use Your Information

We use the collected information for the following core purposes:

1. **Service Fulfillment & Dispatch:** Matching customer requests with nearby verified service providers and dispatching field technicians to project locations.
2. **Quote & Commercial Execution:** Generating transparent service quotes, processing customer approvals, issuing invoices, and managing payments.
3. **Quality & Safety Assurance:** Displaying verified ratings and customer feedback to maintain high standards of service across our provider network.
4. **Audit & Regulatory Compliance:** Maintaining immutable audit traces (`job_status_histories`) for status changes, job overrides, and accounting compliance.

---

## 3. Data Sharing & Disclosure

We do not sell your personal data. Information is shared strictly under the following operational circumstances:

* **Between Matched Parties:** Relevant contact information and job location details are shared between the customer, assigned provider company, and dispatched field technician for service completion.
* **Third-Party Payment Processors:** Financial details are securely processed via industry-standard payment gateways.
* **Legal & Administrative Requirements:** Disclosed when required by applicable law, regulation, or administrative enforcement.

---

## 4. Data Security & Retention

We implement robust administrative, technical, and physical security measures—including TLS/SSL encryption in transit and access-controlled databases—to protect your personal information against unauthorized access, alteration, or disclosure. Personal data is retained only for as long as necessary to fulfill service commitments and satisfy legal, accounting, or regulatory requirements.

---

## 5. Your Choices & Rights

You have the right to access, update, or request the deletion of your account and personal data. Customers and providers can update their profile information directly from their account settings or contact our support team for assistance.

---

## 6. Contact Us

If you have questions or concerns regarding this Privacy Policy, please contact our Data Protection Team at:

* **Email:** privacy@grabahand.com
* **Support Helpline:** 1-800-555-GRAB-A-HAND
MARKDOWN;
    }

    /**
     * Retrieve the singleton settings record (creates default if none exists).
     */
    public static function getSettings(): self
    {
        $settings = self::firstOrCreate(
            ['id' => 1],
            [
                'platform_fee' => 10.00,
                'tax_rate' => 5.00,
                'minimum_booking_charge' => 25.00,
                'privacy_policy' => self::getDefaultPrivacyPolicy(),
            ]
        );

        if (empty($settings->privacy_policy)) {
            $settings->update(['privacy_policy' => self::getDefaultPrivacyPolicy()]);
        }

        return $settings;
    }
}
