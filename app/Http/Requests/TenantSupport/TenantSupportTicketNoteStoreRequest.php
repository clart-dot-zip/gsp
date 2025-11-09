<?php

namespace App\Http\Requests\TenantSupport;

class TenantSupportTicketNoteStoreRequest extends TenantSupportTicketRequest
{
    /**
     * Determine if the user is authorised to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeForTenant()
            && $this->userHasSupportPermission('support_tickets_comment');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isPlayerSession = $this->session()->has('active_player_id');
        $canCollaborate = $this->userHasSupportPermission('support_tickets_collaborate');
        $canAttach = $this->userHasSupportPermission('support_tickets_attach');

        $rules = [
            'body' => ['nullable', 'string'],
            'is_resolution' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'boolean'],
            'timer_seconds' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'integer', 'min:0'],
            'timer_started_at' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'date'],
            'timer_stopped_at' => $isPlayerSession || ! $canCollaborate ? ['prohibited'] : ['nullable', 'date', 'after_or_equal:timer_started_at'],
            'attachments' => $canAttach ? ['nullable', 'array'] : ['prohibited'],
            'attachments.*' => $canAttach ? ['file', 'image', 'max:5120'] : ['prohibited'],
        ];

        return $rules;
    }

    /**
     * Assemble the note payload for persistence.
     */
    public function payload(): array
    {
        $isPlayerSession = $this->session()->has('active_player_id');
        $canCollaborate = $this->userHasSupportPermission('support_tickets_collaborate');

        return [
            'body' => $this->input('body'),
            'is_resolution' => ($isPlayerSession || ! $canCollaborate) ? false : $this->boolean('is_resolution'),
            'timer_seconds' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('timer_seconds'),
            'timer_started_at' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('timer_started_at'),
            'timer_stopped_at' => ($isPlayerSession || ! $canCollaborate) ? null : $this->input('timer_stopped_at'),
        ];
    }

    /**
     * Determine whether the request contains note content.
     */
    public function hasContent(): bool
    {
        $canAttach = $this->userHasSupportPermission('support_tickets_attach');
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

        if ($canAttach && $this->hasFile('attachments')) {
            return true;
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->session()->has('active_player_id')) {
            return;
        }

        if ($this->filled('timer_seconds') && $this->userHasSupportPermission('support_tickets_collaborate')) {
            $minutes = (int) $this->input('timer_seconds');
            $this->merge([
                'timer_seconds' => max(0, $minutes) * 60,
            ]);
        }
    }
}
