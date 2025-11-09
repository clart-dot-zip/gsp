<?php

namespace App\Http\Requests\TenantSupport;

class TenantSupportTicketNoteStoreRequest extends TenantSupportTicketRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeForTenant();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string'],
            'is_resolution' => ['nullable', 'boolean'],
            'timer_seconds' => ['nullable', 'integer', 'min:0'],
            'timer_started_at' => ['nullable', 'date'],
            'timer_stopped_at' => ['nullable', 'date', 'after_or_equal:timer_started_at'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'image', 'max:5120'],
        ];
    }

    /**
     * Assemble the note payload for persistence.
     */
    public function payload(): array
    {
        return [
            'body' => $this->input('body'),
            'is_resolution' => $this->boolean('is_resolution'),
            'timer_seconds' => $this->input('timer_seconds'),
            'timer_started_at' => $this->input('timer_started_at'),
            'timer_stopped_at' => $this->input('timer_stopped_at'),
        ];
    }

    /**
     * Determine whether the request contains note content.
     */
    public function hasContent(): bool
    {
        $payload = $this->payload();

        if (! empty($payload['body'])) {
            return true;
        }

        if (! is_null($payload['timer_seconds']) && (int) $payload['timer_seconds'] > 0) {
            return true;
        }

        if ($payload['is_resolution'] === true) {
            return true;
        }

        return $this->hasFile('attachments');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('timer_seconds')) {
            $minutes = (int) $this->input('timer_seconds');
            $this->merge([
                'timer_seconds' => max(0, $minutes) * 60,
            ]);
        }
    }
}
