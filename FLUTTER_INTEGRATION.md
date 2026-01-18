# GoKoncentrate Dashboard - Flutter/Dart Mobile Integration Guide

**Version:** 1.1.0
**Last Updated:** January 2025
**Backend:** Laravel 11 + Laravel Sanctum
**Mobile:** Flutter/Dart (Android & iOS)

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Token System (KT)](#token-system-kt)
4. [API Endpoints](#api-endpoints)
5. [Dart Models](#dart-models)
6. [Implementation Examples](#implementation-examples)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## Overview

The GoKoncentrate backend provides RESTful APIs for the Flutter mobile apps. All authenticated requests use Laravel Sanctum bearer tokens.

### Base URL
```
Production: https://apps.koncentratemedia.com/api
```

### Authentication Header
```http
Authorization: Bearer {sanctum_token}
Content-Type: application/json
Accept: application/json
```

---

## Authentication

### Login Endpoint
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "token": "1|abc123xyz...",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    }
  },
  "message": "Login successful"
}
```

**Store the token** securely (e.g., `flutter_secure_storage`) and include it in all subsequent authenticated requests.

---

## Token System (KT)

### Overview
- **Currency Name:** GoKoncentrate Tokens
- **Label:** KT
- **Unit:** Cents (100 cents = 1 KT)
- **Storage:** `users.token_balance_cents` (integer)
- **Features:** No cash redemption, no transfers, no blockchain

### How Tokens Work
1. Users earn tokens by watching free content, using focus mode, etc.
2. Tokens can be spent on premium features (future)
3. Admins can credit/debit tokens via the backend
4. All transactions are logged in `token_ledgers` table

### Earning Rules (Configurable by Admin)
| Setting | Default | Description |
|---------|---------|-------------|
| `earn_cents` | 1 | Cents earned per interval |
| `earn_seconds` | 10 | Seconds of engagement per earn |
| `daily_cap_cents` | null | Max daily earnings (null = unlimited) |
| `repeat_cooldown_seconds` | 0 | Cooldown between earnings |

### Eligible Content Types
```json
{
  "free_video": true,
  "free_magazine": true,
  "focus_mode": true
}
```

---

## API Endpoints

### 1. Get Current User & Token Balance

```http
GET /api/me
Authorization: Bearer {token}
```

**Response (200):**
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

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | User ID |
| `name` | string | Full name |
| `first_name` | string | First name |
| `last_name` | string | Last name |
| `email` | string | Email address |
| `token_balance_cents` | int | Balance in cents (100 = 1 KT) |
| `token_balance` | string | Formatted balance ("12.34") |
| `token_label` | string | Currency label ("KT") |

---

### 2. Get Token Settings (Public)

```http
GET /api/token-settings
```

**Response (200):**
```json
{
  "status": true,
  "data": {
    "token_label": "KT",
    "token_name": "GoKoncentrate Tokens",
    "global_enabled": true
  },
  "message": "Token settings retrieved successfully."
}
```

**Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `token_label` | string | Short label for display ("KT") |
| `token_name` | string | Full name ("GoKoncentrate Tokens") |
| `global_enabled` | bool | Whether token system is active |

**Use Case:** Check `global_enabled` before showing token UI. If `false`, hide all token-related features.

---

### 3. Get User Profile Details

```http
GET /api/profile-details
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "status": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "profile_image": "https://apps.koncentratemedia.com/storage/users/image/avatar.webp",
    "token_balance_cents": 1234,
    "token_balance": "12.34"
  },
  "message": "Profile details retrieved successfully."
}
```

---

## Dart Models

### User Model with Token Support

```dart
// lib/models/user.dart

class User {
  final int id;
  final String name;
  final String firstName;
  final String lastName;
  final String email;
  final int tokenBalanceCents;
  final String tokenBalance;
  final String tokenLabel;
  final String? profileImage;

  User({
    required this.id,
    required this.name,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.tokenBalanceCents,
    required this.tokenBalance,
    required this.tokenLabel,
    this.profileImage,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      email: json['email'] ?? '',
      tokenBalanceCents: json['token_balance_cents'] ?? 0,
      tokenBalance: json['token_balance'] ?? '0.00',
      tokenLabel: json['token_label'] ?? 'KT',
      profileImage: json['profile_image'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'token_balance_cents': tokenBalanceCents,
      'token_balance': tokenBalance,
      'token_label': tokenLabel,
      'profile_image': profileImage,
    };
  }

  /// Get formatted display string: "KT 12.34"
  String get tokenDisplayString => '$tokenLabel $tokenBalance';

  /// Get balance as double for calculations
  double get tokenBalanceDouble => tokenBalanceCents / 100.0;
}
```

### Token Settings Model

```dart
// lib/models/token_settings.dart

class TokenSettings {
  final String tokenLabel;
  final String tokenName;
  final bool globalEnabled;

  TokenSettings({
    required this.tokenLabel,
    required this.tokenName,
    required this.globalEnabled,
  });

  factory TokenSettings.fromJson(Map<String, dynamic> json) {
    return TokenSettings(
      tokenLabel: json['token_label'] ?? 'KT',
      tokenName: json['token_name'] ?? 'GoKoncentrate Tokens',
      globalEnabled: json['global_enabled'] ?? false,
    );
  }

  /// Default settings when API fails
  factory TokenSettings.defaults() {
    return TokenSettings(
      tokenLabel: 'KT',
      tokenName: 'GoKoncentrate Tokens',
      globalEnabled: false,
    );
  }
}
```

### API Response Wrapper

```dart
// lib/models/api_response.dart

class ApiResponse<T> {
  final bool status;
  final T? data;
  final String message;

  ApiResponse({
    required this.status,
    this.data,
    required this.message,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>)? fromJsonT,
  ) {
    return ApiResponse<T>(
      status: json['status'] ?? false,
      data: json['data'] != null && fromJsonT != null
          ? fromJsonT(json['data'])
          : null,
      message: json['message'] ?? '',
    );
  }
}
```

---

## Implementation Examples

### API Service

```dart
// lib/services/api_service.dart

import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'https://apps.koncentratemedia.com/api';
  String? _authToken;

  void setAuthToken(String token) {
    _authToken = token;
  }

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_authToken != null) 'Authorization': 'Bearer $_authToken',
  };

  /// GET /api/me - Get current user with token balance
  Future<User> getCurrentUser() async {
    final response = await http.get(
      Uri.parse('$baseUrl/me'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      if (json['status'] == true) {
        return User.fromJson(json['data']);
      }
      throw Exception(json['message'] ?? 'Failed to get user');
    }
    throw Exception('HTTP ${response.statusCode}');
  }

  /// GET /api/token-settings - Get public token configuration
  Future<TokenSettings> getTokenSettings() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/token-settings'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        if (json['status'] == true) {
          return TokenSettings.fromJson(json['data']);
        }
      }
      return TokenSettings.defaults();
    } catch (e) {
      return TokenSettings.defaults();
    }
  }
}
```

### Token Balance Widget

```dart
// lib/widgets/token_balance_widget.dart

import 'package:flutter/material.dart';

class TokenBalanceWidget extends StatelessWidget {
  final String tokenBalance;
  final String tokenLabel;
  final VoidCallback? onTap;

  const TokenBalanceWidget({
    Key? key,
    required this.tokenBalance,
    required this.tokenLabel,
    this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor.withOpacity(0.1),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: Theme.of(context).primaryColor.withOpacity(0.3),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Token icon
            Icon(
              Icons.monetization_on,
              color: Colors.amber,
              size: 18,
            ),
            const SizedBox(width: 6),
            // Balance text
            Text(
              '$tokenLabel $tokenBalance',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Theme.of(context).primaryColor,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

### Profile Screen Integration

```dart
// lib/screens/profile_screen.dart

import 'package:flutter/material.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  User? _user;
  TokenSettings? _tokenSettings;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);

    try {
      final apiService = ApiService();

      // Load token settings and user in parallel
      final results = await Future.wait([
        apiService.getTokenSettings(),
        apiService.getCurrentUser(),
      ]);

      setState(() {
        _tokenSettings = results[0] as TokenSettings;
        _user = results[1] as User;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      // Handle error
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Profile'),
        actions: [
          // Show token balance in app bar if enabled
          if (_tokenSettings?.globalEnabled == true && _user != null)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: Center(
                child: TokenBalanceWidget(
                  tokenBalance: _user!.tokenBalance,
                  tokenLabel: _user!.tokenLabel,
                  onTap: () {
                    // Navigate to token history/details
                  },
                ),
              ),
            ),
          IconButton(
            icon: Icon(Icons.settings),
            onPressed: () {
              // Navigate to settings
            },
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : _buildProfileContent(),
    );
  }

  Widget _buildProfileContent() {
    if (_user == null) {
      return Center(child: Text('Failed to load profile'));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Profile avatar
          CircleAvatar(
            radius: 50,
            backgroundImage: _user!.profileImage != null
                ? NetworkImage(_user!.profileImage!)
                : null,
            child: _user!.profileImage == null
                ? Icon(Icons.person, size: 50)
                : null,
          ),
          const SizedBox(height: 16),

          // Name
          Text(
            _user!.name,
            style: Theme.of(context).textTheme.headlineSmall,
          ),

          // Email
          Text(
            _user!.email,
            style: Theme.of(context).textTheme.bodyMedium,
          ),

          const SizedBox(height: 24),

          // Token balance card (only if enabled)
          if (_tokenSettings?.globalEnabled == true)
            _buildTokenCard(),
        ],
      ),
    );
  }

  Widget _buildTokenCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Icon(Icons.monetization_on, color: Colors.amber, size: 32),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _tokenSettings?.tokenName ?? 'Tokens',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                    ),
                    Text(
                      '${_user!.tokenLabel} ${_user!.tokenBalance}',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              'Earn tokens by watching free content and using Focus Mode',
              style: TextStyle(fontSize: 12, color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
```

### State Management (Provider Example)

```dart
// lib/providers/user_provider.dart

import 'package:flutter/foundation.dart';

class UserProvider extends ChangeNotifier {
  User? _user;
  TokenSettings? _tokenSettings;
  bool _isLoading = false;
  String? _error;

  User? get user => _user;
  TokenSettings? get tokenSettings => _tokenSettings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Check if tokens feature is available
  bool get isTokensEnabled => _tokenSettings?.globalEnabled ?? false;

  /// Get formatted token display
  String get tokenDisplay {
    if (_user == null) return 'KT 0.00';
    return '${_user!.tokenLabel} ${_user!.tokenBalance}';
  }

  final ApiService _apiService = ApiService();

  Future<void> loadUser() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _user = await _apiService.getCurrentUser();
    } catch (e) {
      _error = e.toString();
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadTokenSettings() async {
    try {
      _tokenSettings = await _apiService.getTokenSettings();
      notifyListeners();
    } catch (e) {
      _tokenSettings = TokenSettings.defaults();
      notifyListeners();
    }
  }

  /// Refresh user data (call after any action that might change balance)
  Future<void> refreshUser() async {
    try {
      _user = await _apiService.getCurrentUser();
      notifyListeners();
    } catch (e) {
      // Silently fail on refresh
    }
  }
}
```

---

## Error Handling

### Standard Error Response

```json
{
  "status": false,
  "message": "Error description here",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Process response |
| 401 | Unauthorized | Redirect to login |
| 403 | Forbidden | Show permission error |
| 404 | Not Found | Show not found message |
| 422 | Validation Error | Show field errors |
| 500 | Server Error | Show generic error |

### Dart Error Handler

```dart
// lib/utils/error_handler.dart

class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException({
    required this.statusCode,
    required this.message,
    this.errors,
  });

  @override
  String toString() => message;

  bool get isUnauthorized => statusCode == 401;
  bool get isValidationError => statusCode == 422;
}

ApiException parseError(http.Response response) {
  try {
    final json = jsonDecode(response.body);
    return ApiException(
      statusCode: response.statusCode,
      message: json['message'] ?? 'An error occurred',
      errors: json['errors'],
    );
  } catch (e) {
    return ApiException(
      statusCode: response.statusCode,
      message: 'An error occurred',
    );
  }
}
```

---

## Best Practices

### 1. Always Read Token Balance from Server
```dart
// CORRECT: Get balance from API
final user = await apiService.getCurrentUser();
final balance = user.tokenBalance;

// WRONG: Store balance locally as source of truth
// SharedPreferences should only cache, not be authoritative
```

### 2. Refresh After Token-Related Actions
```dart
// After watching content that earns tokens
await contentService.markAsWatched(contentId);
await userProvider.refreshUser(); // Refresh to get new balance
```

### 3. Check Token System Enabled
```dart
// Always check before showing token UI
if (userProvider.isTokensEnabled) {
  return TokenBalanceWidget(...);
} else {
  return SizedBox.shrink(); // Hide if disabled
}
```

### 4. Handle Offline Gracefully
```dart
// Cache last known balance for offline display
final cachedBalance = await prefs.getString('cached_token_balance') ?? '0.00';

// Update cache when online
if (user != null) {
  await prefs.setString('cached_token_balance', user.tokenBalance);
}
```

### 5. Display Format
```dart
// Standard display format
'$tokenLabel $tokenBalance'  // "KT 12.34"

// For large numbers, consider formatting
if (tokenBalanceCents >= 100000) {
  // Show "1.2K KT" instead of "1234.56 KT"
}
```

---

## Recent Backend Changes (January 2025)

### Tokens Module Added
- New admin panel at `/app/token-settings` for configuring tokens
- New admin panel at `/app/token-users` for managing user balances
- API endpoints: `GET /api/me`, `GET /api/token-settings`

### Image Display Fixed
- Fixed image URL generation on shared hosting
- Profile images now support full URLs from media library
- Default avatar properly served

### Database Changes
Users table now includes:
```sql
token_balance_cents BIGINT UNSIGNED DEFAULT 0
earning_suspended BOOLEAN DEFAULT false
earning_suspended_reason VARCHAR(255) NULL
last_earned_at TIMESTAMP NULL
last_spent_at TIMESTAMP NULL
```

---

## Contact & Support

For integration questions, refer to:
- This document: `FLUTTER_INTEGRATION.md`
- Contract spec: `INTEGRATIONS.md`
- Backend codebase: `Modules/Tokens/`

---

*Document generated for GoKoncentrate Dashboard v1.1.0*
