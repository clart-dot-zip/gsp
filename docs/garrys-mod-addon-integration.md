# Garry's Mod Addon Integration Guide

This guide explains how the Laravel backend exposes tenant-scoped APIs that you can consume from a Garry's Mod addon (ULX compatible). It consolidates the entity layout, authentication model, endpoints, and payload contracts so you can populate and maintain the data the web app expects.

## Authentication
- All integration routes live under `/api/v1/*` and require the `tenant.api` middleware.
- Supply the generated tenant API key via the `X-Api-Key` header or as a bearer token. Keys are hashed with SHA-256 (`tenant_api_keys.key_hash`).
- Every authenticated request resolves a tenant instance and scopes queries/changes to that tenant only.

## Core Data Model
- **Tenant**: container for a customer/server grouping (`tenants`). Holds name, slug, contact email, website, description. `Tenant::displayName()` resolves the user-facing label.
- **TenantPermission**: capability definitions for the tenant (`tenant_permissions`). Each carries a unique slug (auto-generated), optional human description, and `external_reference` for mapping ULX permission strings.
- **TenantGroup**: logical roles (`tenant_groups`). Supports hierarchy via `tenant_group_inheritances` (parent/child), permission assignments via `tenant_group_permission`, and optional `external_reference` for ULX group names.
- **TenantPlayer**: tracked player roster (`tenant_players`) keyed by SteamID64, plus display name, optional avatar URL, and `last_synced_at` for refresh cadence. Group membership uses `tenant_player_group`.
- **TenantActivityLog**: audit trail of integration and dashboard activity. API collectors can append entries via `POST /tenant/logs`.

## Tenant API Endpoints
All endpoints return JSON with a top-level `data` key (except 204 deletes).

### Tenant metadata & admin
- `GET /api/v1/tenant` → basic tenant profile.
- `GET /api/v1/tenant/contacts` → key contact list (role name, SteamID, preferred method).
- `GET /api/v1/tenant/logs` → newest 50 activity log entries.
- `POST /api/v1/tenant/logs` → append custom audit event (addon heartbeat, sync summary, etc.).

### Permission management
- `GET /api/v1/tenant/permissions` → list definitions (id, name, slug, external reference, attached group ids).
- `POST /api/v1/tenant/permissions` → create new definition.
- `GET /api/v1/tenant/permissions/{permission}` → fetch single definition.
- `PUT /api/v1/tenant/permissions/{permission}` → update name/description/external reference.
- `DELETE /api/v1/tenant/permissions/{permission}` → remove definition (cascade detaches from groups).

### Group management
- `GET /api/v1/tenant/groups` → list groups with inheritance, permission details, player IDs, and slug.
- `POST /api/v1/tenant/groups` → create group (optional parent ids & permission ids).
- `GET /api/v1/tenant/groups/{group}` → fetch single group.
- `PUT /api/v1/tenant/groups/{group}` → update metadata, parent_ids, permission_ids.
- `DELETE /api/v1/tenant/groups/{group}` → remove group (detaches from players/children automatically).

### Player management
- `GET /api/v1/tenant/players` → list players with group assignments.
- `POST /api/v1/tenant/players` → create player (enforces unique SteamID per tenant).
- `GET /api/v1/tenant/players/{player}` → fetch single player.
- `PUT /api/v1/tenant/players/{player}` → update profile fields, SteamID, avatar, last synced timestamp, and group assignments.
- `DELETE /api/v1/tenant/players/{player}` → remove player and detach groups.

## Payload Reference
Use the following contracts when marshalling data from ULX.

### Group object (response)
```json
{
  "id": 42,
  "tenant_id": 7,
  "name": "Senior Admin",
  "slug": "senior-admin",
  "description": "High trust moderators",
  "external_reference": "ulx senioradmin",
  "parent_ids": [12],
  "child_ids": [55, 60],
  "player_ids": [101, 102],
  "permissions": [
    { "id": 3, "name": "Kick", "slug": "ulx-kick", "external_reference": "ulx kick" },
    { "id": 5, "name": "Ban", "slug": "ulx-ban", "external_reference": "ulx ban" }
  ]
}
```
`player_ids` helps you back-fill ULX group membership diffs without fetching the entire player document.

### Permission object (response)
```json
{
  "id": 5,
  "tenant_id": 7,
  "name": "Ban",
  "slug": "ulx-ban",
  "description": "Allows permanent bans",
  "external_reference": "ulx ban",
  "group_ids": [42, 43]
}
```

### Player object (response)
```json
{
  "id": 101,
  "tenant_id": 7,
  "display_name": "Clart",
  "steam_id": "76561198000000000",
  "avatar_url": "https://steamcdn.example/avatar.jpg",
  "last_synced_at": "2025-11-09T18:15:27+00:00",
  "group_ids": [42],
  "groups": [
    { "id": 42, "name": "Senior Admin", "slug": "senior-admin" }
  ]
}
```

## JSON Schemas (request bodies)
Schemas describe accepted fields for create/update endpoints. Omitted properties remain unchanged on update.

### Tenant group schema
```json
{
  "$id": "https://gsp.example/schema/tenant-group.json",
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "additionalProperties": false,
  "properties": {
    "name": { "type": "string", "minLength": 1, "maxLength": 255 },
    "description": { "type": ["string", "null"], "maxLength": 1000 },
    "external_reference": { "type": ["string", "null"], "maxLength": 255 },
    "parent_ids": {
      "type": "array",
      "items": { "type": "integer", "minimum": 1 },
      "uniqueItems": true
    },
    "permission_ids": {
      "type": "array",
      "items": { "type": "integer", "minimum": 1 },
      "uniqueItems": true
    }
  },
  "required": ["name"]
}
```

### Tenant permission schema
```json
{
  "$id": "https://gsp.example/schema/tenant-permission.json",
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "additionalProperties": false,
  "properties": {
    "name": { "type": "string", "minLength": 1, "maxLength": 255 },
    "description": { "type": ["string", "null"], "maxLength": 1000 },
    "external_reference": { "type": ["string", "null"], "maxLength": 255 }
  },
  "required": ["name"]
}
```

### Tenant player schema
```json
{
  "$id": "https://gsp.example/schema/tenant-player.json",
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "type": "object",
  "additionalProperties": false,
  "properties": {
    "display_name": { "type": "string", "minLength": 1, "maxLength": 255 },
    "steam_id": { "type": ["string", "null"], "minLength": 1, "maxLength": 64 },
    "avatar_url": { "type": ["string", "null"], "format": "uri", "maxLength": 255 },
    "last_synced_at": { "type": ["string", "null"], "format": "date-time" },
    "group_ids": {
      "type": "array",
      "items": { "type": "integer", "minimum": 1 },
      "uniqueItems": true
    }
  },
  "required": ["display_name"]
}
```

## Integration Tips
- Cache `GET /tenant/groups` and `/tenant/permissions` results locally and refresh on a timer to limit API usage.
- Map ULX group names into `external_reference` so you can reconcile renamed roles.
- When syncing players, send the SteamID64 native to ULX (`ULib.ucl.addUser`). The API enforces uniqueness per tenant and trims whitespace.
- Use `POST /tenant/logs` to record addon lifecycle events (restart, scheduled sync) for administrators.
- Rotate API keys periodically via the admin UI and redeploy to the game server configuration.
