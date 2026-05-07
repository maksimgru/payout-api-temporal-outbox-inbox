<?php

namespace Modules\Payouts\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', Rule::in(['USD', 'EUR', 'usd', 'eur'])],
            'wallet' => ['required', 'string', 'max:255'],
            'external_reference' => ['required', 'string', 'max:128'],
        ];
    }
}
