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

## Version History
- **v1.0** (2025-01-16): Initial implementation with `/api/me` endpoint
