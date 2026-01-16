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

Must return these fields (either directly or inside a `data` object):
```json
{
  "token_balance_cents": 1234,
  "token_balance": "12.34"
}
