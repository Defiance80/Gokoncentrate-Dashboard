# GoKoncentrate – Integration Contract (Laravel ↔ Flutter)

This file defines how the Flutter mobile app integrates with the Laravel backend.
Treat this as the **single source of truth** for integration.

---

## Backend (Laravel)

### Token system
- Name: GoKoncentrate Tokens (KT)
- Storage: `users.token_balance_cents` (BIGINT, default 0)
- 100 cents = 1 KT
- No cash redemption, no transfers, no blockchain

### Required endpoint (authenticated)
`GET /api/me`

**Authentication:** Bearer token via Laravel Sanctum

**Response format:**
```json
{
  "status": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "token_balance_cents": 1234,
    "token_balance": "12.34",
    "token_label": "KT"
  },
  "message": "User details retrieved successfully."
}
```

**Required fields for Flutter:**
| Field | Type | Description |
|-------|------|-------------|
| `token_balance_cents` | int | Balance in cents (100 = 1 KT) |
| `token_balance` | string | Formatted balance ("12.34") |
| `token_label` | string | Currency label ("KT") |

---

## Flutter (Mobile App)

### Profile Screen Token Display
- Location: Next to settings gear icon
- Format: `{token_label} {token_balance}` (e.g., "KT 12.34")
- Source: `GET /api/me` response

### Implementation Notes
- Always read token balance from Laravel (never store locally as source of truth)
- Refresh balance after any action that could change it
- Display `KT 0.00` if fields are missing

---

## Additional Endpoints

### Token Settings (public)
`GET /api/token-settings`

Returns basic token configuration:
```json
{
  "status": true,
  "data": {
    "token_label": "KT",
    "token_name": "GoKoncentrate Tokens",
    "global_enabled": true
  }
}
```

---

## Database Schema Reference

### users table (token fields)
| Column | Type | Default | Description |
|--------|------|---------|-------------|
| token_balance_cents | BIGINT UNSIGNED | 0 | User's token balance in cents |
| earning_suspended | BOOLEAN | false | If true, user cannot earn tokens |
| last_earned_at | TIMESTAMP | null | Last earning activity |
| last_spent_at | TIMESTAMP | null | Last spending activity |

### token_settings table
Single-row configuration for global token settings.

### token_ledgers table
Transaction log for all token movements (audit trail).

---

---

## Magazine / MagCloud Print (Video Magazines + Print Orders)

Video Magazines are managed in the dashboard; the app shows issues with an optional “Print” tab. Print checkout happens on MagCloud; we track intent and redirect via our own URL.

### Public API (no auth required for read)

**List magazine series**
- `GET /api/magazines`
- Returns: list of series (minimal: id, title, slug, cover_image_url, status).

**List issues for a series**
- `GET /api/magazines/{slug}/issues?status=published`
- Returns: issue cards including `print: { enabled, cta_label }` (no MagCloud URLs in list).

**Issue detail**
- `GET /api/magazine-issues/{slug}` (or `GET /api/magazine-issues/{id}` by id)
- Returns: issue with assets, access rules, and **print** (only when print enabled):
  - `print.enabled`, `print.cta_label`, `print.magcloud_product_url`, `print.magcloud_viewer_url` (optional)
  - For app: prefer opening **redirect URL** for tracking (see below).

### Tracking and redirect

**Print redirect (tracking + safe redirect)**
- `GET /r/magazine/{id}/print` or `GET /api/r/magazine/{id}/print`
- Server logs a `click_print` event (optional: session_id, user_id if logged in), then **redirects 302** to the stored MagCloud product URL for that issue.
- Clients should open this URL (in-app browser / WebView or new tab) so all print traffic is tracked and URLs stay server-side.

**Post print event (optional, for app analytics)**
- `POST /api/magazine-issues/{id}/print/events`
- Body: `{ "event": "click_print" | "return_from_magcloud" | "copied_link", "session_id": "..." }`
- Auth: optional (sanctum); `user_id` stored when present.

### Response shape (issue detail, print fragment)

```json
{
  "issue": {
    "id": 1,
    "title": "Koncentrate Vol. 3 — The Builders",
    "slug": "koncentrate-vol-3-builders",
    "print": {
      "enabled": true,
      "cta_label": "Order Collector Print",
      "magcloud_product_url": "https://www.magcloud.com/...",
      "magcloud_viewer_url": "https://www.magcloud.com/...",
      "redirect_url": "https://your-domain.com/r/magazine/1/print"
    }
  }
}
```

When `print.enabled` is false, omit `magcloud_*` and `redirect_url` or return `print: { enabled: false }`.

### Admin (dashboard)

- Magazine series: CRUD (title, slug, cover, description, status).
- Issue editor: basics (title, slug, release date, cover, summary), content (assets), access, and **Print (MagCloud)**:
  - Toggle: Print enabled.
  - If enabled: MagCloud product URL (required, HTTPS, optionally validate host contains `magcloud.com`).
  - Optional: MagCloud viewer/preview URL, CTA label (dropdown or custom).
  - “Test Open” opens redirect URL in new tab.

### Security

- MagCloud URLs stored and validated server-side only; redirect endpoint uses stored URL (no client-supplied URL).
- Auth not shared with MagCloud; no PII passed in query params.

---

---

## Media Radar (automated YouTube / Vimeo discovery)

Media Radar is an **admin-only** feature. It adds no new mobile endpoints and no
new contract for the Flutter app.

Approved candidates are published through the existing publishing pipeline as
normal movies (`entertainments` with `type = movie`), so the app sees them
through the endpoints it already uses. Third-party videos are stored as:

- `video_upload_type` = `YouTube` or `Vimeo`
- `video_url_input` = the provider watch URL

The app plays them the same way it plays any other movie with those upload
types. Cover art may be a remote `https://i.ytimg.com/...` or
`https://i.vimeocdn.com/...` URL rather than a local file name; the app already
handles both because `setBaseUrlWithFileName()` returns remote URLs unchanged.

Provider API keys live in the server environment only and are never returned by
any API response. See `MEDIA_RADAR.md` for the full feature documentation.

## Version History
- **v1.0** (2025-01-16): Initial implementation with `/api/me` endpoint
- **v1.1** (2026-02-04): Magazine / MagCloud print API and redirect contract
- **v1.2** (2026-09-01): Media Radar added (admin only, no mobile contract change)
