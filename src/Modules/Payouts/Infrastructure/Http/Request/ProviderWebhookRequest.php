<?php

namespace Modules\Payouts\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProviderWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:128'],
            'provider_payout_id' => ['nullable', 'string', 'max:128'],
            'external_reference' => ['required', 'string', 'max:128'],
            'status' => ['required', 'string', Rule::in(['processing', 'success', 'failed'])],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
