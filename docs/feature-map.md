# Feature Map

This document summarizes the major features, admin tools, and tenant-facing experiences in the GSP control panel.

## Dashboard
- **Location:** `/dashboard`
- **Purpose:** Landing page for authorized users; surfaces high-level tenant information and quick actions.

## Tenant Pages
- **Location:** `/tenant/pages/{page}` (permission: `view_tenant_pages`)
- **Purpose:** Provides curated tenant-specific pages grouped by category via the sidebar.
- **Notes:** Requires a selected tenant context; navigation automatically disables when no tenant is active.

### Overview
- **Route key:** `overview`
- **Section:** Overview → Tenant Overview
- **What it shows:** Current tenant name, description, primary contact email, website, contact count, and an identifying badge drawn from the slug.
- **Primary users:** Admins and tenant contacts needing a quick status snapshot after selecting a tenant.

### Key Contacts
- **Route key:** `contacts`
- **Section:** Tenant Admin → Key Contacts
- **What it shows:** Summary analytics (total contacts, primary contact, Steam-enabled contacts, preferred channel) and a detailed directory table with mailto links.
- **Deep link:** Buttons jump directly to the manage-contacts workflow for maintenance.

### Activity Logs
- **Route key:** `activity_logs`
- **Section:** Tenant Admin → Activity Logs
- **What it shows:** Paginated audit trail (time, user, event, route, HTTP method, status, IP, trimmed payload) sourced from `tenant_activity_logs`.
- **Use cases:** Compliance review, debugging API usage, correlating in-game actions with panel events.

### Players *(Placeholder)*
- **Route key:** `players`
- **Section:** Manage Services → Players
- **Current state:** Renders a placeholder card indicating the feature is not yet available for the selected tenant.
- **Planned scope:** Will ingest Garry's Mod player roster data via the collector API to expose online status, playtime, and linked identities.

### Bans *(Placeholder)*
- **Route key:** `bans`
- **Section:** Manage Services → Bans
- **Current state:** Placeholder.
- **Planned scope:** Centralize ban list management (issue, lift, sync) between the control panel and in-game admin tools.

### Blacklists *(Placeholder)*
- **Route key:** `blacklists`
- **Section:** Manage Services → Blacklists
- **Current state:** Placeholder.
- **Planned scope:** Track non-player entities (Steam IDs, IPs, phrases) that should be blocked across tenant services.

### Warnings *(Placeholder)*
- **Route key:** `warnings`
- **Section:** Manage Services → Warnings
- **Current state:** Placeholder.
- **Planned scope:** Provide a history of player warnings, including issuing staff, reason, and in-game action follow-up.

### Service Logs *(Placeholder)*
- **Route key:** `logs`
- **Section:** Manage Services → Logs
- **Current state:** Placeholder.
- **Planned scope:** Surface raw integration logs (e.g., sync results, webhook calls) distinct from the tenant activity audit log.

## Manage Tenants
- **Location:** `/tenants/manage` (permission: `manage_tenants`)
- **Purpose:** Create and maintain tenant records, including name, slug, contact email, website, and description.
- **Special Behavior:** Selecting a tenant stores `tenant_id` in the session for downstream contexts.

## Manage Contacts
- **Location:** `/tenants/{tenant}/contacts` (permission: `manage_contacts`)
- **Purpose:** Maintain tenant contact roster; supports CRUD operations, role assignment, and Steam ID linkage.

## Access Control
- **Location:** `/admin/access` (permission: `manage_access`)
- **Purpose:** Manage user-to-group assignments and define permission sets per group.
- **Key Actions:** Attach/detach groups to users, sync permission lists, create new groups.

## Tenant API Keys
- **Location:** `/admin/tenants/api-keys` (permission: `manage_api_keys`)
- **Purpose:** Issue, rotate, and revoke data collector API keys per tenant.
- **Outcome:** Keys enable Garry's Mod integrations to authenticate against `/api/v1/...` endpoints.

## Data Collector API
- **Base path:** `/api/v1` (middleware: `tenant.api`)
- **Authentication:** `X-Api-Key` header or bearer token tied to a tenant-specific key hash.
- **Endpoints:**
  - `GET /tenant` → Core tenant profile metadata
  - `GET /tenant/contacts` → Contact directory used by dashboards or in-game menus
  - `GET /tenant/groups` → Group + permission matrix for role sync
  - `GET /tenant/permissions` → Canonical permission catalog
  - `GET /tenant/logs` → Latest tenant activity entries (50 items)
  - `POST /tenant/logs` → Write integration-originated events into the activity log (used by Garry's Mod collectors)
- **Notes:** Successful requests refresh `tenant_api_keys.last_used_at` for observability.

## Profile Management
- **Location:** `/profile`
- **Purpose:** Allow users to update their name, email, and delete their account (with password confirmation).

## Authentication
- **Login:** `/login` (Authentik and Steam options)
- **Logout:** `/logout`
- **Middleware summary:**
  - `auth` → Redirect unauthenticated visitors
  - `permission` → Enforce feature-level authorization
  - `tenant.activity` → Audit tenant-scoped actions
  - `tenant.api` → Guard API collector routes with key authentication

Document updates should accompany new feature releases or permission model changes.