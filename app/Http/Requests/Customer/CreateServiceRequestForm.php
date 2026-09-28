<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'sub_category_id' => ['nullable', 'exists:service_categories,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'request_type' => ['required', 'in:broadcast,direct'],
            'target_provider_id' => ['required_if:request_type,direct', 'nullable', 'exists:users,id'],
            'description' => ['required', 'string', 'max:2000'],
            'address_line' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_start_time' => ['nullable', 'date_format:H:i'],
            'preferred_end_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['nullable'],
        ];
    }
}
