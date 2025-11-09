<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantDataController extends Controller
{
    public function tenant(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return new JsonResponse([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'contact_email' => $tenant->contact_email,
                'website_url' => $tenant->website_url,
                'description' => $tenant->description,
            ],
        ]);
    }

    public function contacts(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        $contacts = $tenant->contacts()
            ->with('role:id,name')
            ->orderBy('name')
            ->get()
            ->map(static function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'steam_id' => $contact->steam_id,
                    'phone' => $contact->phone,
                    'preferred_method' => $contact->preferred_method,
                    'role' => $contact->role ? $contact->role->name : null,
                    'notes' => $contact->notes,
                ];
            });

        return new JsonResponse(['data' => $contacts]);
    }

    public function logs(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        $logs = $tenant->activityLogs()
            ->latest()
            ->limit(50)
            ->get(['id', 'event', 'method', 'path', 'status_code', 'payload', 'created_at'])
            ->map(static function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'method' => $log->method,
                    'path' => $log->path,
                    'status_code' => $log->status_code,
                    'payload' => $log->payload,
                    'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
                ];
            });

        return new JsonResponse(['data' => $logs]);
    }

    public function storeLog(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        $data = $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:10'],
            'path' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'integer'],
            'payload' => ['nullable', 'array'],
        ]);

        $log = $tenant->activityLogs()->create([
            'event' => $data['event'],
            'method' => strtoupper($data['method'] ?? 'API'),
            'route_name' => 'api.collector',
            'path' => $data['path'] ?? $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => 'data-collector',
            'status_code' => $data['status_code'] ?? null,
            'payload' => $data['payload'] ?? null,
        ]);

        return new JsonResponse([
            'data' => [
                'id' => $log->id,
            ],
        ], 201);
    }
}
