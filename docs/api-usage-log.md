# Data Collector API Usage Log

This log captures expected usage patterns and operational notes for the tenant data collector API. Use it as a living document to track integrations and maintain visibility into how keys are consumed.

## Authentication
- Header: `X-Api-Key` (preferred) or HTTP Bearer token
- Scope: Tenant-specific; each key maps to a single tenant via `tenant_api_keys`
- Rotation: Admin UI (`/admin/tenants/api-keys`) supports create, regenerate, and revoke actions

## Endpoints
| Endpoint | Method | Description | Notes |
| --- | --- | --- | --- |
| `/api/v1/tenant` | GET | Retrieves core tenant profile metadata | Cached per integration recommended
| `/api/v1/tenant/contacts` | GET | Lists tenant contacts with role, Steam ID, and notes | Supports in-game roster sync
| `/api/v1/tenant/groups` | GET | Exposes access groups with attached permissions | Use to map group slugs to in-game roles
| `/api/v1/tenant/permissions` | GET | Returns master permission catalog | Helpful when validating group configurations
| `/api/v1/tenant/logs` | GET | Pulls latest activity log records (50 max) | Designed for dashboards and audit trails
| `/api/v1/tenant/logs` | POST | Pushes integration-originated events into activity log | Include event name and optional payload

## Observability Checklist
- **Key issuance:** Record tenant, admin user, and reason when generating a key.
- **Rotation cadence:** Target quarterly rotation unless an incident requires earlier revocation.
- **Monitoring:** Watch `tenant_api_keys.last_used_at` to identify stale integrations.
- **Anomalies:** Unexpected 4xx/5xx spikes should trigger a review of Auth header usage and payload size.

## Recent Actions
- *2025-11-09* — Data collector API launched; middleware `tenant.api` now enforces key-based auth.
- *2025-11-09* — Admin UI built for key lifecycle management and status visibility per tenant.

Update this document after major integration milestones or policy changes.