<?php

namespace App\Http\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\JobAssignment;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestInvoice = $this->invoices()->latest()->first();
        $isPaid = $this->invoices()->where('status', InvoiceStatus::Paid)->exists();
        $depositPaid = $this->resource->depositPaidAmount();
        $balanceDue = ($latestInvoice && ! $isPaid)
            ? max(0.0, round((float) $latestInvoice->total - (float) $latestInvoice->tip_amount - $depositPaid, 2))
            : 0.0;

        $firstAssignment = $this->relationLoaded('jobAssignments')
            ? $this->jobAssignments->first()
            : ($this->relationLoaded('jobAssignment') ? $this->jobAssignment : null);

        $assignedTech = $firstAssignment?->technician;
        $targetProv = $this->relationLoaded('targetProvider') ? $this->targetProvider : null;
        $assignedProv = $targetProv ?? $firstAssignment?->provider;

        $approvedQuote = null;
        if ($this->relationLoaded('quotes')) {
            $approvedQuote = $this->quotes->firstWhere('is_approved_by_provider', true);
        }

        if (! $assignedProv && $approvedQuote) {
            $assignedProv = $approvedQuote->provider;
        }

        if (! $assignedProv) {
            $resolvedProvId = $this->resolveProviderId();
            if ($resolvedProvId) {
                $assignedProv = User::with('providerProfile')->find($resolvedProvId);
            }
        }

        $canChatTechnician = $firstAssignment !== null;
        $canChatProvider = $assignedProv !== null || $approvedQuote !== null || ($this->relationLoaded('quotes') ? $this->quotes->contains('is_approved_by_provider', true) : $this->quotes()->where('is_approved_by_provider', true)->exists());

        $companyName = $assignedProv?->providerProfile?->company_name ?? $assignedProv?->name ?? ($this->relationLoaded('targetProvider') ? $this->targetProvider?->name : null);

        $techniciansList = [];
        $techRatingsSum = 0.0;
        $techWithRatingsCount = 0;
        $totalTechJobs = 0;

        if ($assignedProv) {
            $provProfile = $assignedProv->providerProfile;
            if ($provProfile) {
                $techProfiles = $provProfile->technicians()->with('user')->get();
                foreach ($techProfiles as $tp) {
                    $techUser = $tp->user;
                    if ($techUser) {
                        $techAvgRating = Review::where('technician_id', $techUser->id)->avg('rating');
                        $jobsCount = (int) (JobAssignment::where('technician_id', $techUser->id)->whereHas('serviceRequest', function ($q) {
                            $q->where('status', ServiceRequestStatus::Completed);
                        })->count());

                        if ($techAvgRating !== null && (float) $techAvgRating > 0) {
                            $techRatingsSum += (float) $techAvgRating;
                            $techWithRatingsCount++;
                        }
                        $totalTechJobs += $jobsCount;

                        $techniciansList[] = [
                            'id' => $techUser->id,
                            'name' => $techUser->name,
                            'phone' => $techUser->phone,
                            'rating' => $techAvgRating !== null ? round((float) $techAvgRating, 1) : null,
                            'completed_jobs_count' => $jobsCount,
                        ];
                    }
                }
            }
        }

        $avgProvReview = $assignedProv ? Review::where('provider_id', $assignedProv->id)->avg('rating') : null;
        $provReviewsCount = $assignedProv ? Review::where('provider_id', $assignedProv->id)->count() : 0;

        $providerRating = null;
        if ($techWithRatingsCount > 0) {
            $providerRating = (string) number_format($techRatingsSum / $techWithRatingsCount, 1);
        } elseif ($avgProvReview !== null) {
            $providerRating = (string) number_format((float) $avgProvReview, 1);
        }

        $providerCompletedJobs = 0;
        if ($totalTechJobs > 0) {
            $providerCompletedJobs = $totalTechJobs;
        } elseif ($assignedProv) {
            $providerCompletedJobs = (int) ServiceRequest::where(function ($q) use ($assignedProv) {
                $q->where('target_provider_id', $assignedProv->id)
                    ->orWhereHas('jobAssignments', function ($ja) use ($assignedProv) {
                        $ja->where('provider_id', $assignedProv->id);
                    })
                    ->orWhereHas('providerJobRequests', function ($pjr) use ($assignedProv) {
                        $pjr->where('provider_id', $assignedProv->id)
                            ->whereIn('status', ['accepted', 'selected']);
                    });
            })->where('status', ServiceRequestStatus::Completed)->count();
        }

        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'customer_id' => $this->customer_id,
            'is_paid' => $isPaid,
            'payment_status' => match (true) {
                $isPaid => 'paid',
                (bool) $this->deposit_paid_at => 'deposit_paid',
                default => 'deposit_pending',
            },
            'customer' => new UserResource($this->whenLoaded('customer')),
            'deposit_amount' => (float) $this->deposit_amount,
            'deposit_paid' => (bool) $this->deposit_paid_at,
            'deposit_paid_at' => $this->deposit_paid_at?->toIso8601String(),
            'upfront_deposit_paid' => $depositPaid,
            'minimum_booking_charge' => (float) $this->deposit_amount,
            'invoice_id' => $latestInvoice?->id,
            'invoice_total' => $latestInvoice ? round((float) $latestInvoice->total - (float) $latestInvoice->tip_amount, 2) : null,
            'balance_due' => $balanceDue,
            'service_category_id' => $this->service_category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'icon' => $this->category->icon,
                    'image' => $this->category->image_url ?? $this->category->image,
                    'minimum_booking_charge' => (float) $this->category->effective_minimum_booking_charge,
                ];
            }),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'slug' => $this->subCategory->slug,
                    'minimum_booking_charge' => (float) $this->subCategory->effective_minimum_booking_charge,
                ];
            }),
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'base_price' => $this->service->base_price,
                ];
            }),
            'request_type' => $this->request_type?->value,
            'description' => $this->description,
            'address_line' => $this->address_line,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'preferred_start_time' => $this->preferred_start_time,
            'preferred_end_time' => $this->preferred_end_time,
            'timezone' => $this->timezone,
            'priority' => $this->priority,
            'status' => $this->status?->value,
            'provider_id' => $assignedProv?->id ?? $approvedQuote?->provider_id ?? $firstAssignment?->provider_id ?? $this->target_provider_id,
            'provider_name' => $assignedProv?->name ?? ($this->relationLoaded('targetProvider') ? $this->targetProvider?->name : null),
            'company_name' => $companyName,
            'provider_phone' => $assignedProv?->phone,
            'provider_rating' => $providerRating,
            'provider_completed_jobs' => $providerCompletedJobs > 0 ? "{$providerCompletedJobs} jobs" : '0 jobs',
            'provider_reviews_count' => $provReviewsCount,
            'technicians' => $techniciansList,
            'technician_id' => $assignedTech?->id ?? $firstAssignment?->technician_id,
            'technician_name' => $assignedTech?->name ?? ($this->relationLoaded('jobAssignments') ? $this->jobAssignments->first()?->technician?->name : null),
            'technician_phone' => $assignedTech?->phone,
            'technician_status' => $firstAssignment?->status?->value,
            'technician_assigned_at' => $firstAssignment?->created_at?->toIso8601String(),
            'is_cancelled' => $this->status === ServiceRequestStatus::Cancelled,
            'can_chat_technician' => (bool) $canChatTechnician,
            'can_chat_provider' => (bool) $canChatProvider,
            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->map(function ($photo) {
                    $path = $photo->file_path;
                    if (Str::startsWith($path, ['http://', 'https://'])) {
                        return $path;
                    }

                    $cleanPath = ltrim($path, '/');
                    if (Str::startsWith($cleanPath, 'storage/')) {
                        $cleanPath = Str::after($cleanPath, 'storage/');
                    } elseif (Str::startsWith($cleanPath, 'public/')) {
                        $cleanPath = Str::after($cleanPath, 'public/');
                    }

                    return asset('storage/'.$cleanPath);
                });
            }),
            'review' => $this->whenLoaded('review', function () {
                return [
                    'id' => $this->review->id,
                    'rating' => (int) $this->review->rating,
                    'comment' => $this->review->comment,
                    'created_at' => $this->review->created_at?->toIso8601String(),
                    'updated_at' => $this->review->updated_at?->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
