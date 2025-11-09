@php
    use App\Models\TenantSupportTicket;
    use Illuminate\Support\Facades\Auth;
    use App\Models\User;
    use App\Models\TenantContact;

    $statusOptions = [
        TenantSupportTicket::STATUS_OPEN => 'Open',
        TenantSupportTicket::STATUS_IN_PROGRESS => 'In Progress',
        TenantSupportTicket::STATUS_RESOLVED => 'Resolved',
        TenantSupportTicket::STATUS_CLOSED => 'Closed',
    ];

    $priorityOptions = [
        TenantSupportTicket::PRIORITY_LOW => 'Low',
        TenantSupportTicket::PRIORITY_NORMAL => 'Normal',
        TenantSupportTicket::PRIORITY_HIGH => 'High',
        TenantSupportTicket::PRIORITY_CRITICAL => 'Critical',
    ];

    $authUser = Auth::user();
    $playerSessionActive = isset($isPlayerSession) ? (bool) $isPlayerSession : (bool) ($supportTicketPermissions['is_player'] ?? false);
    $canComment = $supportTicketPermissions['can_comment'] ?? false;
    $canAttachFiles = $supportTicketPermissions['can_attach'] ?? false;
@endphp

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card card-outline card-secondary mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Filters</h3>
            </div>
            <form method="GET" action="{{ route('tenants.pages.show', ['page' => 'support_tickets']) }}">
                <div class="card-body">
                    <div class="form-group">
                        <label for="filter-status">Status</label>
                        <select id="filter-status" name="status" class="form-control">
                            <option value="">All</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($supportTicketFilters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-priority">Priority</label>
                        <select id="filter-priority" name="priority" class="form-control">
                            <option value="">All</option>
                            @foreach ($priorityOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($supportTicketFilters['priority'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter-search">Search</label>
                        <input type="search" id="filter-search" name="search" value="{{ $supportTicketFilters['search'] ?? '' }}" class="form-control" placeholder="Subject or reference">
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('tenants.pages.show', ['page' => 'support_tickets']) }}" class="btn btn-light">Reset</a>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>

        @if ($supportTicketPermissions['can_create'])
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">New Ticket</h3>
                </div>
                <form method="POST" action="{{ route('tenants.support.tickets.store', ['tenant' => $tenant]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="ticket-subject">Subject</label>
                            <input type="text" id="ticket-subject" name="subject" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="ticket-priority">Priority</label>
                            <select id="ticket-priority" name="priority" class="form-control">
                                @foreach ($priorityOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ticket-description">Initial Description <span class="text-muted">(supports Markdown)</span></label>
                            <textarea id="ticket-description" name="description" rows="4" class="form-control" placeholder="Describe the situation, include bullet points with '-' or '*' where needed."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="ticket-assignees">Assign To <span class="text-muted">(optional)</span></label>
                            @if ($playerSessionActive)
                                <p class="form-control-plaintext text-muted mb-0">Assignments are handled by staff.</p>
                            @else
                                <select id="ticket-assignees" name="assignees[]" class="form-control" multiple>
                                    @foreach ($supportAgents as $agent)
                                        <option value="user:{{ $agent->id }}">{{ $agent->name }} (Admin)</option>
                                    @endforeach
                                    @foreach ($supportContacts as $contact)
                                        <option value="contact:{{ $contact->id }}">{{ $contact->name }} (Tenant)</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="ticket-players">Related Players <span class="text-muted">(optional)</span></label>
                            @if ($playerSessionActive)
                                <p class="form-control-plaintext text-muted mb-0">Tickets you create will be linked to your player profile automatically.</p>
                            @else
                                <select id="ticket-players" name="players[]" class="form-control" multiple>
                                    @foreach ($supportPlayers as $player)
                                        <option value="{{ $player->id }}">{{ $player->display_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="ticket-note">Ticket Notes <span class="text-muted">(optional)</span></label>
                            <textarea id="ticket-note" name="note_body" rows="3" class="form-control" placeholder="Add extra context for staff members."></textarea>
                        </div>
                        @unless ($playerSessionActive)
                            <div class="form-group">
                                <label for="ticket-timer">Timer (minutes) <span class="text-muted">(optional)</span></label>
                                <input type="number" id="ticket-timer" name="note_timer_seconds" min="0" step="1" class="form-control" placeholder="e.g. 15 for a quarter-hour">
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="ticket-resolution" name="note_is_resolution" value="1">
                                    <label class="custom-control-label" for="ticket-resolution">Mark note as resolution</label>
                                </div>
                            </div>
                        @endunless
                        @if ($canAttachFiles)
                            <div class="form-group">
                                <label for="ticket-attachments">Attachments <span class="text-muted">(images only)</span></label>
                                <input type="file" id="ticket-attachments" name="attachments[]" class="form-control-file" accept="image/*" multiple>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success">Create Ticket</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-lg-9">
        @if ($supportTickets && $supportTickets->count() > 0)
            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="list-group">
                        @foreach ($supportTickets as $ticket)
                            @php
                                $isActive = (int) ($supportTicketHighlightId ?? ($selectedTicket?->id ?? 0)) === (int) $ticket->id;
                                $statusBadgeClasses = [
                                    TenantSupportTicket::STATUS_OPEN => 'badge badge-info',
                                    TenantSupportTicket::STATUS_IN_PROGRESS => 'badge badge-warning',
                                    TenantSupportTicket::STATUS_RESOLVED => 'badge badge-success',
                                    TenantSupportTicket::STATUS_CLOSED => 'badge badge-secondary',
                                ];
                                $badgeClass = $statusBadgeClasses[$ticket->status] ?? 'badge badge-light';
                                $linkParameters = array_merge(request()->query(), [
                                    'page' => 'support_tickets',
                                    'highlight_ticket' => $ticket->id,
                                ]);
                            @endphp
                            <a href="{{ route('tenants.pages.show', $linkParameters) }}" class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ $ticket->subject }}</span>
                                    <span class="{{ $badgeClass }}">{{ $statusOptions[$ticket->status] ?? ucfirst($ticket->status) }}</span>
                                </div>
                                <div class="small text-muted">Opened {{ $ticket->opened_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}</div>
                                <div class="small mt-1">
                                    <span class="mr-2">Priority: {{ $ticket->priorityLabel() }}</span>
                                    <span class="mr-2">Notes: {{ $ticket->notes_count }}</span>
                                    <span>Players: {{ $ticket->players_count }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $supportTickets->links() }}
                    </div>
                </div>

                <div class="col-md-7">
                    @if ($selectedTicket)
                        @php
                            $assignedLabels = $selectedTicket->assignees->map(function ($assignment) {
                                $assignee = $assignment->assignee;
                                if ($assignee instanceof User) {
                                    return $assignee->name;
                                }
                                if ($assignee instanceof TenantContact) {
                                    return $assignee->name;
                                }
                                return 'Unknown';
                            })->implode(', ');
                            $currentUserAssigned = $authUser instanceof User ? $selectedTicket->isAssignedTo($authUser) : false;
                            $canManageTicket = $supportTicketPermissions['can_manage'];
                            $canCollaborate = $supportTicketPermissions['can_collaborate'];
                            $canAddNote = $canCollaborate || $canComment;
                        @endphp

                        <div class="card card-outline card-primary">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title mb-0">{{ $selectedTicket->subject }}</h3>
                                    <p class="mb-0 text-sm text-muted">
                                        Reference:
                                        <span class="ml-1 font-weight-semibold text-nowrap text-white">
                                            {{ $selectedTicket->external_reference ?? 'Not synced yet' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-info">{{ $statusOptions[$selectedTicket->status] ?? ucfirst($selectedTicket->status) }}</span>
                                    <span class="badge badge-secondary">Priority: {{ $selectedTicket->priorityLabel() }}</span>
                                    <p class="mb-0 text-muted text-sm">Opened {{ $selectedTicket->opened_at?->diffForHumans() ?? $selectedTicket->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($selectedTicket->description)
                                    <div class="mb-3">
                                        {!! nl2br(e($selectedTicket->description)) !!}
                                    </div>
                                @endif

                                <dl class="row">
                                    <dt class="col-sm-3">Assignees</dt>
                                    <dd class="col-sm-9">{{ $assignedLabels ?: 'Unassigned' }}</dd>

                                    <dt class="col-sm-3">Players</dt>
                                    <dd class="col-sm-9">
                                        @if ($selectedTicket->players->isEmpty())
                                            <span class="text-muted">None linked</span>
                                        @else
                                            {{ $selectedTicket->players->pluck('display_name')->implode(', ') }}
                                        @endif
                                    </dd>
                                </dl>

                                @if ($canCollaborate)
                                    <form method="POST" action="{{ route('tenants.support.tickets.update', ['tenant' => $tenant, 'ticket' => $selectedTicket]) }}" class="border rounded p-3 mb-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="ticket-status-{{ $selectedTicket->id }}">Status</label>
                                                <select id="ticket-status-{{ $selectedTicket->id }}" name="status" class="form-control">
                                                    @foreach ($statusOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ $selectedTicket->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="ticket-priority-{{ $selectedTicket->id }}">Priority</label>
                                                <select id="ticket-priority-{{ $selectedTicket->id }}" name="priority" class="form-control">
                                                    @foreach ($priorityOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ $selectedTicket->priority === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="ticket-assignees-{{ $selectedTicket->id }}">Assignees</label>
                                                <select id="ticket-assignees-{{ $selectedTicket->id }}" name="assignees[]" class="form-control" multiple>
                                                    @foreach ($supportAgents as $agent)
                                                        <option value="user:{{ $agent->id }}" {{ $selectedTicket->assignees->contains(fn ($assignment) => $assignment->assignee_type === User::class && $assignment->assignee_id === $agent->id) ? 'selected' : '' }}>
                                                            {{ $agent->name }} (Admin)
                                                        </option>
                                                    @endforeach
                                                    @foreach ($supportContacts as $contact)
                                                        <option value="contact:{{ $contact->id }}" {{ $selectedTicket->assignees->contains(fn ($assignment) => $assignment->assignee_type === TenantContact::class && $assignment->assignee_id === $contact->id) ? 'selected' : '' }}>
                                                            {{ $contact->name }} (Tenant)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="ticket-players-{{ $selectedTicket->id }}">Players</label>
                                            <select id="ticket-players-{{ $selectedTicket->id }}" name="players[]" class="form-control" multiple>
                                                @foreach ($supportPlayers as $player)
                                                    <option value="{{ $player->id }}" {{ $selectedTicket->players->contains('id', $player->id) ? 'selected' : '' }}>
                                                        {{ $player->display_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="text-right">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                @endif

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="text-muted">Ticket ID {{ $selectedTicket->id }}</span>
                                    </div>
                                    <div>
                                        @if ($canCollaborate)
                                            @if ($currentUserAssigned)
                                                <form method="POST" action="{{ route('tenants.support.tickets.release', ['tenant' => $tenant, 'ticket' => $selectedTicket]) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-warning btn-sm">Unclaim</button>
                                                </form>
                                            @elseif ($selectedTicket->canBeClaimedBy($authUser))
                                                <form method="POST" action="{{ route('tenants.support.tickets.claim', ['tenant' => $tenant, 'ticket' => $selectedTicket]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Claim Ticket</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <div class="timeline">
                                    @foreach ($selectedTicket->notes as $note)
                                        @php
                                            $author = $note->author;
                                            $noteLabel = 'System';
                                            if ($author instanceof User) {
                                                $noteLabel = $author->name;
                                            } elseif ($author instanceof TenantContact) {
                                                $noteLabel = $author->name.' (Tenant)';
                                            }
                                        @endphp
                                        <div class="time-label">
                                            <span class="bg-{{ $note->is_resolution ? 'success' : 'primary' }}">{{ $note->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-comment bg-gray"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="far fa-clock"></i> {{ $note->created_at->diffForHumans() }}</span>
                                                <h3 class="timeline-header">{{ $noteLabel }}</h3>
                                                <div class="timeline-body">
                                                    @if ($note->body)
                                                        {!! nl2br(e($note->body)) !!}
                                                    @else
                                                        <span class="text-muted">No text provided.</span>
                                                    @endif

                                                    @if ($note->hasTimer())
                                                        <div class="mt-2">
                                                            <span class="badge badge-info">Timer: {{ gmdate('H\h i\m s\s', $note->timer_seconds) }}</span>
                                                        </div>
                                                    @endif

                                                    @if ($note->attachments->isNotEmpty())
                                                        <ul class="list-unstyled mt-2">
                                                            @foreach ($note->attachments as $attachment)
                                                                <li class="mb-1">
                                                                    <a href="{{ $attachment->temporaryUrl() }}" target="_blank" rel="noopener">{{ $attachment->original_name }}</a>
                                                                    @if ($canCollaborate)
                                                                        <form method="POST" action="{{ route('tenants.support.tickets.attachments.destroy', ['tenant' => $tenant, 'ticket' => $selectedTicket, 'attachment' => $attachment]) }}" class="d-inline">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0 ml-2">Remove</button>
                                                                        </form>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                                @php
                                                    $canDeleteNote = false;
                                                    if ($canManageTicket) {
                                                        $canDeleteNote = true;
                                                    } elseif ($authUser instanceof User) {
                                                        if (! $authUser->isTenantContact() && $note->author instanceof User && (int) $note->author->id === (int) $authUser->id) {
                                                            $canDeleteNote = true;
                                                        }

                                                        if ($authUser->isTenantContact() && $authUser->tenantContact && $note->author instanceof TenantContact && (int) $note->author->id === (int) $authUser->tenantContact->id) {
                                                            $canDeleteNote = true;
                                                        }
                                                    }
                                                @endphp
                                                @if ($canCollaborate && $canDeleteNote)
                                                    <div class="timeline-footer">
                                                        <form method="POST" action="{{ route('tenants.support.tickets.notes.destroy', ['tenant' => $tenant, 'ticket' => $selectedTicket, 'note' => $note]) }}" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-xs">Delete Note</button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    @if ($canAddNote)
                                        <div>
                                            <i class="fas fa-plus bg-green"></i>
                                            <div class="timeline-item">
                                                <h3 class="timeline-header">Add Note</h3>
                                                <div class="timeline-body">
                                                    <form method="POST" action="{{ route('tenants.support.tickets.notes.store', ['tenant' => $tenant, 'ticket' => $selectedTicket]) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="form-group">
                                                            <label for="note-body-{{ $selectedTicket->id }}">Details</label>
                                                            <textarea id="note-body-{{ $selectedTicket->id }}" name="body" rows="3" class="form-control" placeholder="Update the ticket with new findings"></textarea>
                                                        </div>
                                                        @unless ($playerSessionActive)
                                                            <div class="form-row">
                                                                <div class="form-group col-md-4">
                                                                    <label for="note-timer-{{ $selectedTicket->id }}">Timer (minutes)</label>
                                                                    <input type="number" id="note-timer-{{ $selectedTicket->id }}" name="timer_seconds" class="form-control" min="0" step="1" placeholder="Optional">
                                                                </div>
                                                                <div class="form-group col-md-4">
                                                                    <label for="note-start-{{ $selectedTicket->id }}">Timer Started</label>
                                                                    <input type="datetime-local" id="note-start-{{ $selectedTicket->id }}" name="timer_started_at" class="form-control">
                                                                </div>
                                                                <div class="form-group col-md-4">
                                                                    <label for="note-stop-{{ $selectedTicket->id }}">Timer Stopped</label>
                                                                    <input type="datetime-local" id="note-stop-{{ $selectedTicket->id }}" name="timer_stopped_at" class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input type="checkbox" class="custom-control-input" id="note-resolution-{{ $selectedTicket->id }}" name="is_resolution" value="1">
                                                                    <label class="custom-control-label" for="note-resolution-{{ $selectedTicket->id }}">Mark as resolution</label>
                                                                </div>
                                                            </div>
                                                        @endunless
                                                        @if ($canAttachFiles)
                                                            <div class="form-group">
                                                                <label for="note-attachments-{{ $selectedTicket->id }}">Attachments <span class="text-muted">(images only)</span></label>
                                                                <input type="file" id="note-attachments-{{ $selectedTicket->id }}" name="attachments[]" class="form-control-file" accept="image/*" multiple>
                                                            </div>
                                                        @endif
                                                        <div class="text-right">
                                                            <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card card-outline card-secondary">
                            <div class="card-body">
                                <p class="mb-0 text-muted">Select a ticket from the list to view its details and timeline.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="card card-outline card-secondary">
                <div class="card-body">
                    <p class="mb-0 text-muted">No support tickets have been logged yet. Once a ticket is created in-game or via this portal it will appear here.</p>
                </div>
            </div>
        @endif
    </div>
</div>
