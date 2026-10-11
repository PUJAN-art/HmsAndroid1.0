# Hostel Management System — Mobile API Documentation

**Production Base URL:** `https://hostelmmp.sital.info.np`
**Local Development Base URL:** `http://127.0.0.1:8000`
**API Prefix:** `/api`  
**Auth:** Laravel Sanctum — Bearer token  
**Content-Type:** `application/json`  
**Accept:** `application/json`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Health Check](#1-health-check)
3. [Register — General](#2-register--general)
4. [Student Register](#3-student-register)
5. [General Login](#4-general-login-all-roles)
6. [Student Login](#5-student-login-students-only)
7. [Student Profile](#6-student-profile--protected)
8. [Logout](#7-logout--protected)
9. [Get Current User](#8-get-current-user--protected)
10. [Change Password](#9-change-password--protected)
11. [Password Reset — Send OTP](#10-password-reset--send-otp)
12. [Password Reset — Verify OTP](#11-password-reset--verify-otp)
13. [Password Reset — Set New Password](#12-password-reset--set-new-password--protected)
14. [Rooms — List All](#13-rooms--list-all)
15. [Rooms — Available Only](#14-rooms--available-only)
16. [Rooms — Single Detail](#15-rooms--single-detail)
17. [Fees — My Fees](#16-fees--my-fees--protected)
18. [Fees — My Receipts](#17-fees--my-receipts--protected)
19. [Attendance — My Records](#18-attendance--my-records--protected)
20. [Attendance — Check In](#19-attendance--check-in--protected)
21. [Attendance — Check Out](#20-attendance--check-out--protected)
22. [Complaints — Submit](#21-complaints--submit--protected)
23. [Complaints — My Complaints](#22-complaints--my-complaints--protected)
24. [Visitors — Log Request](#23-visitors--log-request--protected)
25. [Visitors — My Visitors](#24-visitors--my-visitors--protected)
26. [Notices — List All](#25-notices--list-all--protected)
27. [Admin Login](#26-admin-login)
28. [Admin Dashboard](#27-admin-dashboard--protected)
29. [Error Reference](#error-reference)

---

## Authentication

Protected routes require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {your_token_here}
```

Tokens are obtained from the login endpoints.  
Each login revokes the previous token for that device before issuing a new one.

---

## Endpoints

---

### 1. Health Check

Check that the API is reachable.

**`GET /api/test`**

No auth required.

**Response `200`**
```json
{
  "status": "success",
  "message": "API working"
}
```

---

### 2. Register

Create a new student or visitor account. Account requires admin approval before login is allowed.

**`POST /api/auth/register`**

No auth required.

**Request Body**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | ✅ | Max 255 chars |
| `email` | string | ✅ | Must be unique |
| `password` | string | ✅ | Must pass Laravel password rules |
| `password_confirmation` | string | ✅ | Must match `password` |
| `phone` | string | ✅ | Max 20 chars |
| `role` | string | ✅ | `student` or `visitor` |
| `parent_phone` | string | If role=student | Max 20 chars |
| `year` | integer | If role=student | 1–4 |
| `department` | string | If role=student | Max 100 chars |
| `semester` | integer | If role=student | 1–8 |
| `address` | string | If role=student | Max 255 chars |
| `student_id_number` | string | If role=student | Must be unique |

**Example Request**
```json
{
  "name": "Ram Sharma",
  "email": "ram@example.com",
  "password": "SecurePass@123",
  "password_confirmation": "SecurePass@123",
  "phone": "9800000000",
  "role": "student",
  "parent_phone": "9811111111",
  "year": 2,
  "department": "Computer",
  "semester": 4,
  "address": "Morang, Nepal",
  "student_id_number": "HMS-00005"
}
```

**Response `201`**
```json
{
  "message": "Registration successful. Please wait for admin approval before logging in."
}
```

**Response `422` — Validation error**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### 3. Student Register

Dedicated registration for students. Accepts the 7 student fields, saves to the `users` table with `role = student`, and returns a Bearer token immediately. The token is valid but **protected routes will reject the student until an admin approves the account** (`is_approved = true`).

**`POST /api/student/register`**

No auth required.

**Request Body**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | ✅ | Max 255 chars |
| `email` | string | ✅ | Must be unique |
| `contact` | string | ✅ | Phone number, max 20 chars |
| `gender` | string | ✅ | `male`, `female`, or `other` |
| `address` | string | ✅ | Max 500 chars |
| `admission_no` | string | ✅ | Must be unique, max 50 chars |
| `password` | string | ✅ | Min 8 chars, must include uppercase, number, symbol |
| `password_confirmation` | string | ✅ | Must match `password` |

**Example Request**
```json
{
  "name": "Ram Sharma",
  "email": "ram@example.com",
  "contact": "9800000000",
  "gender": "male",
  "address": "Morang, Nepal",
  "admission_no": "ADM-2024-001",
  "password": "SecurePass@123",
  "password_confirmation": "SecurePass@123"
}
```

**Response `201` — Success**
```json
{
  "status": "success",
  "message": "Registration successful. Please wait for admin approval before you can log in.",
  "token": "1|abc123xyz...",
  "student": {
    "id": 12,
    "name": "Ram Sharma",
    "email": "ram@example.com",
    "contact": "9800000000",
    "gender": "male",
    "address": "Morang, Nepal",
    "admission_no": "ADM-2024-001",
    "is_approved": false,
    "is_active": true
  }
}
```

**Response `422` — Validation error**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."],
    "admission_no": ["The admission no has already been taken."]
  }
}
```

> **Note:** The token returned here can be stored by the mobile app, but calls to protected endpoints will return `403` until the admin approves the student account from the web dashboard.

---

### 4. General Login (All Roles)

Login for any role — student, visitor, or admin.

**`POST /api/auth/login`**

No auth required.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Example Request**
```json
{
  "email": "ram@example.com",
  "password": "SecurePass@123"
}
```

**Response `200` — Success**
```json
{
  "message": "Login successful.",
  "token": "1|abc123xyz...",
  "user": {
    "id": 5,
    "name": "Ram Sharma",
    "email": "ram@example.com",
    "role": "student",
    "phone": "9800000000",
    "department": "Computer",
    "year": 2,
    "semester": 4,
    "student_id_number": "HMS-00005",
    "is_approved": true,
    "is_active": true,
    "profile_photo_path": "photos/ram.jpg",
    "blood_group": "B+",
    "bed_id": 3,
    "locale": "en",
    "theme": "light"
  }
}
```

**Response `401` — Wrong credentials**
```json
{
  "message": "The provided credentials do not match our records."
}
```

**Response `403` — Pending approval**
```json
{
  "message": "Your account is pending admin approval."
}
```

**Response `403` — Deactivated**
```json
{
  "message": "Your account has been deactivated. Please contact the administrator."
}
```

---

### 5. Student Login (Students Only)

Dedicated login endpoint for students. Silently rejects admin/visitor accounts — does not reveal that the account exists under a different role.

**`POST /api/student/login`**

No auth required.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Example Request**
```json
{
  "email": "ram@example.com",
  "password": "SecurePass@123"
}
```

**Response `200` — Success**
```json
{
  "status": "success",
  "message": "Login successful.",
  "token": "2|def456uvw...",
  "student": {
    "id": 5,
    "name": "Ram Sharma",
    "email": "ram@example.com",
    "phone": "9800000000",
    "student_id_number": "HMS-00005",
    "department": "Computer",
    "year": 2,
    "semester": 4,
    "blood_group": "B+",
    "address": "Morang, Nepal",
    "profile_photo_url": "http://localhost/storage/photos/ram.jpg",
    "bed": {
      "bed_id": 3,
      "bed_number": "B1",
      "room_number": "101",
      "room_type": "standard"
    },
    "id_card_expiry_date": "2026-07-01",
    "is_active": true
  }
}
```

**Response `401` — Wrong credentials or non-student account**
```json
{
  "status": "error",
  "message": "The provided credentials do not match our records."
}
```

**Response `403` — Pending approval**
```json
{
  "status": "error",
  "message": "Your account is pending admin approval."
}
```

**Response `403` — Deactivated**
```json
{
  "status": "error",
  "message": "Your account has been deactivated. Please contact the administrator."
}
```

> **Security note:** Non-student accounts (admin, visitor) receive the same `401` response as wrong credentials. This prevents role enumeration.

---

### 6. Student Profile *(Protected)*

Returns the fully authenticated student's details including assigned room, bed, pending fees summary, and attendance count for the current month.

**`GET /api/student/profile`**

Requires `Authorization: Bearer {token}`

**No request body needed.**

**How to call:**
```
GET http://localhost/api/student/profile
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

**Response `200` — Success**
```json
{
  "status": "success",
  "student": {
    "id": 5,
    "name": "Ram Sharma",
    "email": "ram@example.com",
    "contact": "9800000000",
    "gender": "male",
    "address": "Morang, Nepal",
    "admission_no": "ADM-2024-001",
    "student_id_number": "HMS-00005",
    "profile_photo_url": "http://localhost/storage/photos/ram.jpg",
    "department": "Computer",
    "year": 2,
    "semester": 4,
    "blood_group": "B+",
    "emergency_contact": "9811111111",
    "emergency_contact_name": "Ram's Father",
    "id_card_expiry_date": "2026-07-01",
    "is_approved": true,
    "is_active": true,
    "room": {
      "room_id": 3,
      "room_number": "101",
      "room_type": "standard",
      "room_status": "active",
      "room_photo_url": "http://localhost/storage/rooms/101.jpg",
      "capacity": 4,
      "bed_id": 7,
      "bed_number": "B2"
    },
    "pending_fees": {
      "count": 2,
      "total": 5000
    },
    "attendance_this_month": 18
  }
}
```

**Response when no room assigned — `room` is null**
```json
{
  "status": "success",
  "student": {
    "id": 5,
    "name": "Ram Sharma",
    ...
    "room": null,
    "pending_fees": { "count": 0, "total": 0 },
    "attendance_this_month": 0
  }
}
```

**Response `401` — Missing or invalid token**
```json
{
  "message": "Unauthenticated."
}
```

**Response `403` — Token belongs to admin or visitor**
```json
{
  "status": "error",
  "message": "Access denied. This endpoint is for students only."
}
```

**Fields returned:**

| Field | Description |
|-------|-------------|
| `id` | User ID |
| `name` | Full name |
| `email` | Email address |
| `contact` | Phone number |
| `gender` | `male` / `female` / `other` |
| `address` | Permanent address |
| `admission_no` | Admission number |
| `student_id_number` | HMS student ID |
| `profile_photo_url` | Full URL to profile photo or `null` |
| `department` | Academic department |
| `year` | Academic year (1–4) |
| `semester` | Semester (1–8) |
| `blood_group` | Blood group |
| `emergency_contact` | Emergency contact number |
| `emergency_contact_name` | Emergency contact name |
| `id_card_expiry_date` | ID card expiry date |
| `is_approved` | Admin approval status |
| `is_active` | Account active status |
| `room` | Assigned room + bed info, or `null` |
| `room.room_id` | Room database ID |
| `room.room_number` | Room number (e.g. `"101"`) |
| `room.room_type` | `standard` or `deluxe` |
| `room.room_status` | `active` or `maintenance` |
| `room.room_photo_url` | Full URL to room photo or `null` |
| `room.capacity` | Total beds in room |
| `room.bed_id` | Assigned bed database ID |
| `room.bed_number` | Assigned bed label (e.g. `"B2"`) |
| `pending_fees.count` | Number of unpaid fee records |
| `pending_fees.total` | Total unpaid amount in Rs. |
| `attendance_this_month` | Present days this calendar month |

---

### 7. Logout *(Protected)*

Revokes the current Bearer token.

**`POST /api/auth/logout`**

Requires `Authorization: Bearer {token}`

**Response `200`**
```json
{
  "message": "Logged out successfully."
}
```

---

### 8. Get Current User *(Protected)*

Returns the profile of the currently authenticated user.

**`GET /api/auth/me`**

Requires `Authorization: Bearer {token}`

**Response `200`**
```json
{
  "id": 5,
  "name": "Ram Sharma",
  "email": "ram@example.com",
  "role": "student",
  "phone": "9800000000",
  "department": "Computer",
  "year": 2,
  "semester": 4,
  "student_id_number": "HMS-00005",
  "is_approved": true,
  "is_active": true,
  "profile_photo_path": "photos/ram.jpg",
  "blood_group": "B+",
  "bed_id": 3,
  "locale": "en",
  "theme": "light"
}
```

---

### 9. Change Password *(Protected)*

Change password while logged in. Requires the current password.

**`PUT /api/auth/password`**

Requires `Authorization: Bearer {token}`

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `current_password` | string | ✅ |
| `password` | string | ✅ | Must pass Laravel password rules |
| `password_confirmation` | string | ✅ | Must match `password` |

**Example Request**
```json
{
  "current_password": "OldPass@123",
  "password": "NewPass@456",
  "password_confirmation": "NewPass@456"
}
```

**Response `200`**
```json
{
  "message": "Password updated successfully."
}
```

**Response `422` — Wrong current password**
```json
{
  "message": "The current password field is incorrect.",
  "errors": {
    "current_password": ["The current password field is incorrect."]
  }
}
```

---

### 10. Password Reset — Send OTP

Step 1 of 3 for password reset. Sends a 6-digit OTP to the user's email. Always returns a success-like message to prevent email enumeration.

**`POST /api/auth/password/send-otp`**

No auth required.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |

**Example Request**
```json
{
  "email": "ram@example.com"
}
```

**Response `200`**
```json
{
  "message": "If that email exists, an OTP has been sent."
}
```

> OTP expires in **10 minutes**.

---

### 11. Password Reset — Verify OTP

Step 2 of 3. Submit the OTP received by email. Returns a short-lived `reset_token` valid for **15 minutes**.

**`POST /api/auth/password/verify-otp`**

No auth required.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `otp` | string | ✅ | Exactly 6 characters |

**Example Request**
```json
{
  "email": "ram@example.com",
  "otp": "482910"
}
```

**Response `200`**
```json
{
  "message": "OTP verified.",
  "reset_token": "3|ghi789rst..."
}
```

**Response `422` — Invalid or expired OTP**
```json
{
  "message": "Invalid or expired OTP."
}
```

---

### 12. Password Reset — Set New Password *(Protected)*

Step 3 of 3. Use the `reset_token` from step 2 as the Bearer token. Token is invalidated after use.

**`POST /api/auth/password/reset`**

Requires `Authorization: Bearer {reset_token}`

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `password` | string | ✅ | Must pass Laravel password rules |
| `password_confirmation` | string | ✅ | Must match `password` |

**Example Request**
```json
{
  "password": "NewPass@789",
  "password_confirmation": "NewPass@789"
}
```

**Response `200`**
```json
{
  "message": "Password reset successfully. You can now log in."
}
```

**Response `403` — Invalid or expired reset token**
```json
{
  "message": "Invalid or expired reset token."
}
```

---

### 13. Rooms — List All

Returns all rooms with their occupied and available bed counts. No token required.

**`GET /api/rooms`**

No auth required.

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `type` | `standard` \| `deluxe` | Filter by room type |
| `status` | `active` \| `maintenance` | Filter by room status |

**Example Requests**
```
GET http://localhost/api/rooms
GET http://localhost/api/rooms?type=deluxe
GET http://localhost/api/rooms?status=active
GET http://localhost/api/rooms?type=standard&status=active
```

**Response `200`**
```json
{
  "status": "success",
  "total": 3,
  "rooms": [
    {
      "id": 1,
      "room_number": "101",
      "type": "standard",
      "status": "active",
      "capacity": 4,
      "occupied_beds": 3,
      "available_beds": 1,
      "occupancy_rate": 75.0,
      "room_photo_url": "http://localhost/storage/rooms/101.jpg"
    },
    {
      "id": 2,
      "room_number": "102",
      "type": "deluxe",
      "status": "active",
      "capacity": 2,
      "occupied_beds": 2,
      "available_beds": 0,
      "occupancy_rate": 100.0,
      "room_photo_url": null
    },
    {
      "id": 3,
      "room_number": "103",
      "type": "standard",
      "status": "maintenance",
      "capacity": 4,
      "occupied_beds": 0,
      "available_beds": 4,
      "occupancy_rate": 0.0,
      "room_photo_url": null
    }
  ]
}
```

**Fields:**

| Field | Description |
|-------|-------------|
| `id` | Room database ID |
| `room_number` | Room identifier (e.g. `"101"`) |
| `type` | `standard` or `deluxe` |
| `status` | `active` or `maintenance` |
| `capacity` | Total number of beds in the room |
| `occupied_beds` | Number of beds currently assigned to students |
| `available_beds` | Number of vacant beds |
| `occupancy_rate` | Percentage of beds occupied (0–100) |
| `room_photo_url` | Full URL to room photo, or `null` |

---

### 14. Rooms — Available Only

Returns only rooms that are `active` and have at least one vacant bed. Use this to show students rooms they can request.

**`GET /api/rooms/available`**

No auth required.

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `type` | `standard` \| `deluxe` | Filter by room type |

**Example Requests**
```
GET http://localhost/api/rooms/available
GET http://localhost/api/rooms/available?type=deluxe
```

**Response `200`**
```json
{
  "status": "success",
  "total": 1,
  "rooms": [
    {
      "id": 1,
      "room_number": "101",
      "type": "standard",
      "status": "active",
      "capacity": 4,
      "occupied_beds": 3,
      "available_beds": 1,
      "occupancy_rate": 75.0,
      "room_photo_url": "http://localhost/storage/rooms/101.jpg"
    }
  ]
}
```

> Rooms with `status = maintenance` are **never** returned here, even if they have vacant beds.

---

### 15. Rooms — Single Detail

Returns full details of one room including a complete bed list showing which beds are occupied and which are vacant.

**`GET /api/rooms/{id}`**

No auth required.

**URL Parameter**

| Param | Type | Description |
|-------|------|-------------|
| `id` | integer | The room's database ID |

**Example Request**
```
GET http://localhost/api/rooms/1
```

**Response `200`**
```json
{
  "status": "success",
  "room": {
    "id": 1,
    "room_number": "101",
    "type": "standard",
    "status": "active",
    "capacity": 4,
    "occupied_beds": 3,
    "available_beds": 1,
    "occupancy_rate": 75.0,
    "room_photo_url": "http://localhost/storage/rooms/101.jpg",
    "beds": [
      { "id": 1, "bed_number": "A1", "is_occupied": true },
      { "id": 2, "bed_number": "A2", "is_occupied": true },
      { "id": 3, "bed_number": "A3", "is_occupied": true },
      { "id": 4, "bed_number": "A4", "is_occupied": false }
    ]
  }
}
```

**Response `404` — Room not found**
```json
{
  "status": "error",
  "message": "Room not found."
}
```

> The `beds` array only appears on this single-room endpoint, not on the list endpoints.

---

### 16. Fees — My Fees *(Protected)*

Returns the authenticated student's complete fee records with a summary of totals, and each fee includes its payment receipts embedded.

**`GET /api/fees/my`**

Requires `Authorization: Bearer {token}`

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `status` | `pending` \| `paid` \| `overdue` | Filter by fee status |
| `type` | `monthly` \| `penalty` \| `mess` | Filter by fee type |

**Example Requests**
```
GET http://localhost/api/fees/my
GET http://localhost/api/fees/my?status=pending
GET http://localhost/api/fees/my?type=monthly&status=overdue
```

**Response `200`**
```json
{
  "status": "success",
  "summary": {
    "total_fees": 6,
    "paid_count": 4,
    "pending_count": 1,
    "overdue_count": 1,
    "total_amount": 12000.00,
    "paid_amount": 8000.00,
    "pending_amount": 2000.00,
    "overdue_amount": 2000.00,
    "due_amount": 4000.00
  },
  "total": 6,
  "fees": [
    {
      "id": 10,
      "type": "monthly",
      "amount": 2000.00,
      "due_date": "2026-07-01",
      "status": "pending",
      "created_at": "2026-06-01",
      "receipts": []
    },
    {
      "id": 9,
      "type": "monthly",
      "amount": 2000.00,
      "due_date": "2026-06-01",
      "status": "paid",
      "created_at": "2026-05-01",
      "receipts": [
        {
          "receipt_id": 5,
          "fee_id": 9,
          "amount_paid": 2000.00,
          "payment_date": "2026-05-28",
          "method": "cash",
          "transaction_id": null,
          "issued_at": "2026-05-28 10:30:00"
        }
      ]
    }
  ]
}
```

**Summary fields:**

| Field | Description |
|-------|-------------|
| `total_fees` | Total number of fee records for this student |
| `paid_count` | Count of fees with `status = paid` |
| `pending_count` | Count of fees with `status = pending` |
| `overdue_count` | Count of fees with `status = overdue` |
| `total_amount` | Sum of all fee amounts (Rs.) |
| `paid_amount` | Sum of paid fee amounts (Rs.) |
| `pending_amount` | Sum of pending fee amounts (Rs.) |
| `overdue_amount` | Sum of overdue fee amounts (Rs.) |
| `due_amount` | `pending_amount + overdue_amount` — total still owed |

> The `summary` always reflects **all** fees regardless of filters applied. Filters only affect the `fees[]` array.

**Response `403` — Non-student token**
```json
{
  "status": "error",
  "message": "Access denied. This endpoint is for students only."
}
```

---

### 17. Fees — My Receipts *(Protected)*

Returns a flat list of all payment receipts for the authenticated student. Each receipt includes its parent fee details for context.

**`GET /api/fees/receipts`**

Requires `Authorization: Bearer {token}`

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `method` | `cash` \| `online` | Filter by payment method |
| `fee_id` | integer | Receipts for one specific fee only |

**Example Requests**
```
GET http://localhost/api/fees/receipts
GET http://localhost/api/fees/receipts?method=cash
GET http://localhost/api/fees/receipts?fee_id=9
```

**Response `200`**
```json
{
  "status": "success",
  "total_paid": 8000.00,
  "total": 4,
  "receipts": [
    {
      "receipt_id": 5,
      "fee_id": 9,
      "amount_paid": 2000.00,
      "payment_date": "2026-05-28",
      "method": "cash",
      "transaction_id": null,
      "issued_at": "2026-05-28 10:30:00",
      "fee": {
        "fee_id": 9,
        "type": "monthly",
        "amount": 2000.00,
        "due_date": "2026-06-01",
        "status": "paid"
      }
    },
    {
      "receipt_id": 4,
      "fee_id": 8,
      "amount_paid": 2000.00,
      "payment_date": "2026-04-25",
      "method": "online",
      "transaction_id": "TXN-ABC123",
      "issued_at": "2026-04-25 14:15:00",
      "fee": {
        "fee_id": 8,
        "type": "monthly",
        "amount": 2000.00,
        "due_date": "2026-05-01",
        "status": "paid"
      }
    }
  ]
}
```

**Receipt fields:**

| Field | Description |
|-------|-------------|
| `receipt_id` | Payment database ID |
| `fee_id` | Parent fee ID |
| `amount_paid` | Amount paid in this payment (Rs.) |
| `payment_date` | Date payment was made |
| `method` | `cash` or `online` |
| `transaction_id` | Transaction reference for online payments, `null` for cash |
| `issued_at` | Full datetime the payment record was created |
| `fee.fee_id` | Parent fee ID |
| `fee.type` | `monthly`, `penalty`, or `mess` |
| `fee.amount` | Original fee amount |
| `fee.due_date` | Fee due date |
| `fee.status` | Current fee status |
| `total_paid` | Sum of all receipt amounts returned |

**Response `403` — Non-student token**
```json
{
  "status": "error",
  "message": "Access denied. This endpoint is for students only."
}
```

---

### 18. Attendance — My Records *(Protected)*

Returns the authenticated student's attendance records for a given month, with a summary block showing present/absent/late day counts.

**`GET /api/attendance/my`**

Requires `Authorization: Bearer {token}`

**Optional Query Parameters**

| Param | Format | Description |
|-------|--------|-------------|
| `month` | `YYYY-MM` | Month to retrieve (default: current month) |
| `status` | `present` \| `absent` \| `late` | Filter records by status |

**Example Requests**
```
GET http://localhost/api/attendance/my
GET http://localhost/api/attendance/my?month=2026-05
GET http://localhost/api/attendance/my?status=late
GET http://localhost/api/attendance/my?month=2026-06&status=present
```

**Response `200`**
```json
{
  "status": "success",
  "month": "2026-06",
  "summary": {
    "total_days": 22,
    "present_days": 19,
    "absent_days": 2,
    "late_days": 1,
    "attendance_percentage": 86.4
  },
  "total": 22,
  "records": [
    {
      "id": 45,
      "date": "2026-06-24",
      "status": "present",
      "time_in": "08:30 AM",
      "time_out": "05:15 PM",
      "time_in_raw": "08:30:00",
      "time_out_raw": "17:15:00",
      "created_at": "2026-06-24 08:30:00"
    },
    {
      "id": 44,
      "date": "2026-06-23",
      "status": "late",
      "time_in": "10:45 AM",
      "time_out": "05:00 PM",
      "time_in_raw": "10:45:00",
      "time_out_raw": "17:00:00",
      "created_at": "2026-06-23 10:45:00"
    }
  ]
}
```

> `summary` always reflects the **full month** regardless of `?status=` filter. The filter only affects the `records[]` array.

**Response `422` — Invalid month format**
```json
{
  "status": "error",
  "message": "Invalid month format. Use YYYY-MM (e.g. 2026-06)."
}
```

---

### 19. Attendance — Check In *(Protected)*

Records the student's check-in time for today. Creates a new attendance record if none exists, or updates an existing one created by admin. Automatically marks status as `late` if check-in is after 10:00 AM.

**`POST /api/attendance/checkin`**

Requires `Authorization: Bearer {token}`

**No request body needed.**

**Example Request**
```
POST http://localhost/api/attendance/checkin
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

**Response `201` — Success**
```json
{
  "status": "success",
  "message": "Check-in recorded successfully.",
  "attendance": {
    "id": 46,
    "date": "2026-06-24",
    "status": "present",
    "time_in": "08:25 AM",
    "time_out": null,
    "time_in_raw": "08:25:00",
    "time_out_raw": null,
    "created_at": "2026-06-24 08:25:00"
  }
}
```

**Response `201` — Late check-in (after 10:00 AM)**
```json
{
  "status": "success",
  "message": "Check-in recorded successfully.",
  "attendance": {
    "id": 46,
    "date": "2026-06-24",
    "status": "late",
    "time_in": "10:45 AM",
    ...
  }
}
```

**Response `409` — Already checked in today**
```json
{
  "status": "error",
  "message": "You have already checked in today.",
  "checked_in_at": "08:25 AM",
  "attendance": { ... }
}
```

---

### 20. Attendance — Check Out *(Protected)*

Records the student's check-out time for today. Requires a prior check-in. Returns the total duration of stay.

**`POST /api/attendance/checkout`**

Requires `Authorization: Bearer {token}`

**No request body needed.**

**Example Request**
```
POST http://localhost/api/attendance/checkout
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

**Response `200` — Success**
```json
{
  "status": "success",
  "message": "Check-out recorded successfully.",
  "duration": "8h 50m",
  "attendance": {
    "id": 46,
    "date": "2026-06-24",
    "status": "present",
    "time_in": "08:25 AM",
    "time_out": "05:15 PM",
    "time_in_raw": "08:25:00",
    "time_out_raw": "17:15:00",
    "created_at": "2026-06-24 08:25:00"
  }
}
```

**Response `422` — No check-in found for today**
```json
{
  "status": "error",
  "message": "You must check in before checking out."
}
```

**Response `409` — Already checked out today**
```json
{
  "status": "error",
  "message": "You have already checked out today.",
  "checked_out_at": "05:15 PM",
  "attendance": { ... }
}
```

**Attendance record fields:**

| Field | Description |
|-------|-------------|
| `id` | Attendance record ID |
| `date` | Date of attendance (`YYYY-MM-DD`) |
| `status` | `present`, `absent`, or `late` |
| `time_in` | Check-in time in 12-hour format (`08:30 AM`), or `null` |
| `time_out` | Check-out time in 12-hour format, or `null` |
| `time_in_raw` | Check-in time in 24-hour `HH:MM:SS` format |
| `time_out_raw` | Check-out time in 24-hour `HH:MM:SS` format |

---

### 21. Complaints — Submit *(Protected)*

Student submits a new complaint. Notifies the admin and logs the action — identical behaviour to the web form.

**`POST /api/complaints`**

Requires `Authorization: Bearer {token}`

**Request Body**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `category` | string | ✅ | One of: `plumbing`, `electricity`, `food`, `cleanliness`, `security`, `other` |
| `description` | string | ✅ | Min 10 chars, max 2000 chars |

**Example Request**
```json
{
  "category": "plumbing",
  "description": "The bathroom tap in room 101 has been leaking for three days."
}
```

**Response `201` — Success**
```json
{
  "status": "success",
  "message": "Complaint submitted successfully.",
  "complaint": {
    "id": 12,
    "category": "plumbing",
    "description": "The bathroom tap in room 101 has been leaking for three days.",
    "status": "pending",
    "admin_remark": null,
    "submitted_at": "2026-06-24 09:15:00",
    "updated_at": "2026-06-24 09:15:00"
  }
}
```

**Response `422` — Validation error**
```json
{
  "message": "The category field must be one of: plumbing, electricity, food, cleanliness, security, other.",
  "errors": {
    "category": ["The category field must be one of: plumbing, electricity, food, cleanliness, security, other."],
    "description": ["The description field must be at least 10 characters."]
  }
}
```

**Response `403` — Non-student token**
```json
{
  "status": "error",
  "message": "Access denied. Only students can submit complaints."
}
```

---

### 22. Complaints — My Complaints *(Protected)*

Returns all complaints submitted by the authenticated student, with a summary of counts by status.

**`GET /api/complaints/my`**

Requires `Authorization: Bearer {token}`

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `status` | `pending` \| `open` \| `in_progress` \| `resolved` | Filter by complaint status |
| `category` | `plumbing` \| `electricity` \| `food` \| `cleanliness` \| `security` \| `other` | Filter by category |

**Example Requests**
```
GET http://localhost/api/complaints/my
GET http://localhost/api/complaints/my?status=pending
GET http://localhost/api/complaints/my?category=plumbing
GET http://localhost/api/complaints/my?status=resolved&category=electricity
```

**Response `200`**
```json
{
  "status": "success",
  "summary": {
    "total": 5,
    "pending": 2,
    "open": 1,
    "in_progress": 1,
    "resolved": 1
  },
  "total": 5,
  "complaints": [
    {
      "id": 12,
      "category": "plumbing",
      "description": "The bathroom tap in room 101 has been leaking for three days.",
      "status": "pending",
      "admin_remark": null,
      "submitted_at": "2026-06-24 09:15:00",
      "updated_at": "2026-06-24 09:15:00"
    },
    {
      "id": 9,
      "category": "electricity",
      "description": "The ceiling fan in room 101 stopped working.",
      "status": "resolved",
      "admin_remark": "Fan replaced on 2026-06-20.",
      "submitted_at": "2026-06-18 14:30:00",
      "updated_at": "2026-06-20 11:00:00"
    }
  ]
}
```

**Complaint fields:**

| Field | Description |
|-------|-------------|
| `id` | Complaint database ID |
| `category` | Type of complaint |
| `description` | Full complaint text |
| `status` | `pending` → `open` → `in_progress` → `resolved` |
| `admin_remark` | Admin's response or action note, `null` if not yet reviewed |
| `submitted_at` | When the complaint was submitted |
| `updated_at` | When the complaint was last updated by admin |

> `summary` always reflects **all** complaints regardless of filters applied.

---

### 23. Visitors — Log Request *(Protected)*

Student submits a visitor request for a specific date. The request starts with `status = pending` and requires admin approval before the visitor can enter.

**`POST /api/visitors`**

Requires `Authorization: Bearer {token}`

**Request Body**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `visitor_name` | string | ✅ | Max 255 chars |
| `phone` | string | ❌ | Visitor's contact number, max 20 chars |
| `purpose` | string | ✅ | Reason for visit, min 5, max 500 chars |
| `visit_date` | date | ✅ | `YYYY-MM-DD`, must be today or a future date |

**Example Request**
```json
{
  "visitor_name": "Hari Bahadur",
  "phone": "9855001122",
  "purpose": "Attending my son's hostel parent-teacher meeting",
  "visit_date": "2026-07-05"
}
```

**Response `201` — Success**
```json
{
  "status": "success",
  "message": "Visitor request submitted successfully. Awaiting admin approval.",
  "visitor": {
    "id": 18,
    "visitor_name": "Hari Bahadur",
    "phone": "9855001122",
    "purpose": "Attending my son's hostel parent-teacher meeting",
    "visit_date": "2026-07-05",
    "status": "pending",
    "entry_time": null,
    "exit_time": null,
    "submitted_at": "2026-06-24 11:00:00",
    "updated_at": "2026-06-24 11:00:00"
  }
}
```

**Response `422` — Validation error**
```json
{
  "message": "The visit date field must be a date after or equal to today.",
  "errors": {
    "visit_date": ["The visit date field must be a date after or equal to today."],
    "purpose": ["The purpose field must be at least 5 characters."]
  }
}
```

**Response `403` — Non-student token**
```json
{
  "status": "error",
  "message": "Access denied. Only students can submit visitor requests."
}
```

> After submission, the admin receives a notification in the web dashboard. `entry_time` and `exit_time` are set by admin when the visitor physically arrives/leaves.

---

### 24. Visitors — My Visitors *(Protected)*

Returns all visitor requests submitted by the authenticated student, newest first, with a summary by status.

**`GET /api/visitors/my`**

Requires `Authorization: Bearer {token}`

**Optional Query Parameters**

| Param | Values | Description |
|-------|--------|-------------|
| `status` | `pending` \| `approved` \| `rejected` | Filter by request status |
| `visit_date` | `YYYY-MM-DD` | Filter by exact visit date |

**Example Requests**
```
GET http://localhost/api/visitors/my
GET http://localhost/api/visitors/my?status=approved
GET http://localhost/api/visitors/my?visit_date=2026-07-05
GET http://localhost/api/visitors/my?status=pending&visit_date=2026-07-05
```

**Response `200`**
```json
{
  "status": "success",
  "summary": {
    "total": 5,
    "pending": 2,
    "approved": 2,
    "rejected": 1
  },
  "total": 5,
  "visitors": [
    {
      "id": 18,
      "visitor_name": "Hari Bahadur",
      "phone": "9855001122",
      "purpose": "Attending my son's hostel parent-teacher meeting",
      "visit_date": "2026-07-05",
      "status": "pending",
      "entry_time": null,
      "exit_time": null,
      "submitted_at": "2026-06-24 11:00:00",
      "updated_at": "2026-06-24 11:00:00"
    },
    {
      "id": 15,
      "visitor_name": "Sita Devi",
      "phone": "9800112233",
      "purpose": "Family visit",
      "visit_date": "2026-06-20",
      "status": "approved",
      "entry_time": "2026-06-20 14:00:00",
      "exit_time": "2026-06-20 17:30:00",
      "submitted_at": "2026-06-18 09:00:00",
      "updated_at": "2026-06-20 17:30:00"
    }
  ]
}
```

**Visitor record fields:**

| Field | Description |
|-------|-------------|
| `id` | Visitor request database ID |
| `visitor_name` | Name of the visitor |
| `phone` | Visitor's contact number or `null` |
| `purpose` | Stated reason for visit |
| `visit_date` | Planned visit date (`YYYY-MM-DD`) |
| `status` | `pending` → `approved` or `rejected` |
| `entry_time` | Actual entry datetime (set by admin on arrival), `null` if not yet arrived |
| `exit_time` | Actual exit datetime (set by admin on departure), `null` if not yet departed |
| `submitted_at` | When the request was submitted |
| `updated_at` | When the record was last updated |

> `summary` always reflects **all** visitor requests regardless of filters applied.

---

### 25. Notices — List All *(Protected)*

Returns all notices ordered by latest first. Available to any authenticated user (student or admin).

**`GET /api/notices`**

Requires `Authorization: Bearer {token}`

**No request body needed.**

**Example Request**
```
GET http://localhost/api/notices
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

**Response `200`**
```json
{
  "status": "success",
  "total": 3,
  "notices": [
    {
      "id": 10,
      "title": "Hostel Closed for Dashain",
      "description": "The hostel will remain closed from October 1 to October 10 for Dashain vacation. All students must vacate by September 30.",
      "created_date": "2026-09-25 10:00:00",
      "posted_by": "Admin"
    },
    {
      "id": 9,
      "title": "Room Maintenance Notice",
      "description": "Room 201 will undergo electrical maintenance on July 15. Students are requested to cooperate.",
      "created_date": "2026-07-10 14:30:00",
      "posted_by": "Admin"
    },
    {
      "id": 8,
      "title": "Mess Menu Updated for July",
      "description": "The new mess menu for July has been uploaded. Please check the notice board for details.",
      "created_date": "2026-07-01 08:00:00",
      "posted_by": "Administrator"
    }
  ]
}
```

**Notice fields:**

| Field | Description |
|-------|-------------|
| `id` | Notice database ID |
| `title` | Notice title |
| `description` | Full notice content |
| `created_date` | When the notice was posted |
| `posted_by` | Name of the user who posted it, or `"Administrator"` if unknown |

> No filters or pagination are applied — all notices are returned at once, newest first.

---

### 26. Admin Login

Authenticates an admin user. Validates against the `users` table where `role = admin`. Non-admin accounts receive the same `401` as wrong credentials (prevents role enumeration).

**`POST /api/admin/login`**

No auth required.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Example Request**
```json
{
  "email": "admin@hms.com",
  "password": "Admin@123"
}
```

**Response `200` — Success**
```json
{
  "status": "success",
  "message": "Login successful.",
  "token": "3|admin-token-here...",
  "admin": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@hms.com",
    "role": "admin"
  }
}
```

**Response `401` — Wrong credentials or non-admin account**
```json
{
  "status": "error",
  "message": "The provided credentials do not match our records."
}
```

**Response `403` — Deactivated**
```json
{
  "status": "error",
  "message": "Your account has been deactivated. Please contact the administrator."
}
```

> Admin tokens are named `admin-mobile`. A new login revokes all previous `admin-mobile` tokens for that account.

---

### 27. Admin Dashboard *(Protected)*

Returns summary counts for the admin dashboard. Requires an admin Bearer token.

**`GET /api/admin/dashboard`**

Requires `Authorization: Bearer {token}`

**No request body needed.**

**Example Request**
```
GET http://localhost/api/admin/dashboard
Authorization: Bearer 3|admin-token-here...
Accept: application/json
```

**Response `200`**
```json
{
  "status": "success",
  "data": {
    "total_students": 45,
    "total_rooms": 12,
    "total_complaints": 8,
    "due_fees": 6,
    "due_fees_amount": 12000.00
  }
}
```

**Fields:**

| Field | Description |
|-------|-------------|
| `total_students` | Number of registered students (role = `student`) |
| `total_rooms` | Total rooms in the system |
| `total_complaints` | Total complaints submitted |
| `due_fees` | Number of fees with status `pending` or `overdue` |
| `due_fees_amount` | Sum of all due fee amounts in Rs. |

> `due_fees` counts both pending and overdue fee records. `due_fees_amount` is the total monetary value of those unpaid fees.

**Response `403` — Non-admin token**
```json
{
  "status": "error",
  "message": "Access denied. This endpoint is for admins only."
}
```

---

## Error Reference

API routes (`/api/*`) return standardized JSON error responses for common HTTP errors.
Web routes keep their normal HTML error pages.

### Standard JSON Error Shapes

| HTTP Code | `status` field | `message` field |
|-----------|----------------|-----------------|
| `401` | `"unauthenticated"` | `"Login required"` |
| `404` | `"error"` | `"Not found"` |
| `500` | `"error"` | `"Server error"` |

### Per-endpoint Error Codes

| HTTP Code | Meaning |
|-----------|---------|
| `200` | Success |
| `201` | Created |
| `401` | Unauthenticated — wrong credentials, missing/invalid token, or `{"status":"unauthenticated","message":"Login required"}` |
| `403` | Forbidden — account not approved, deactivated, or wrong token ability |
| `404` | Route not found — `{"status":"error","message":"Not found"}` |
| `422` | Validation failed — see `errors` object in response |
| `500` | Server error — `{"status":"error","message":"Server error"}` |

> These JSON responses are only returned for requests to `/api/*` routes or when the `Accept: application/json` header is set. Standard HTML error pages are served for web routes.

---

## Password Rules

Laravel default password rules apply:
- Minimum **8 characters**
- At least **1 uppercase letter**
- At least **1 number**
- At least **1 symbol**

---

## Complete Route Summary

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| `GET` | `http://localhost/api/test` | ❌ | Health check |
| `POST` | `http://localhost/api/auth/register` | ❌ | Register student or visitor (general) |
| `POST` | `http://localhost/api/student/register` | ❌ | Register student (dedicated) |
| `POST` | `http://localhost/api/auth/login` | ❌ | Login (all roles) |
| `POST` | `http://localhost/api/student/login` | ❌ | Login (students only) |
| `GET` | `http://localhost/api/rooms` | ❌ | List all rooms with bed counts |
| `GET` | `http://localhost/api/rooms/available` | ❌ | List only available rooms |
| `GET` | `http://localhost/api/rooms/{id}` | ❌ | Single room with full bed list |
| `GET` | `http://localhost/api/student/rooms` | ✅ student | Student room list (compatibility path) |
| `GET` | `http://localhost/api/student/rooms/available` | ✅ student | Available student rooms |
| `POST` | `http://localhost/api/student/rooms/{id}/request` | ✅ student | Request the room currently being viewed |
| `POST` | `http://localhost/api/room-requests` | ✅ student | Submit a room request with `room_id` in JSON |
| `GET` | `http://localhost/api/student/room-requests` | ✅ student | List the student's room requests |
| `GET` | `http://localhost/api/student/room-requests/{id}` | ✅ student | Get one of the student's room requests |
| `DELETE` | `http://localhost/api/student/room-requests/{id}` | ✅ student | Cancel a pending room request |
| `POST` | `http://localhost/api/auth/logout` | ✅ | Logout |
| `GET` | `http://localhost/api/auth/me` | ✅ | Get current user |
| `GET` | `http://localhost/api/student/profile` | ✅ | Get student profile + room info |
| `GET` | `http://localhost/api/student/fees` | ✅ student | Student fee records and summary |
| `GET` | `http://localhost/api/student/fees/receipts` | ✅ student | Student payment receipts |
| `GET` | `http://localhost/api/student/attendance` | ✅ student | Student attendance and monthly summary |
| `POST` | `http://localhost/api/student/attendance/checkin` | ✅ student | Record today's check-in |
| `POST` | `http://localhost/api/student/attendance/checkout` | ✅ student | Record today's check-out |
| `GET` | `http://localhost/api/student/complaints` | ✅ student | List student's complaints |
| `POST` | `http://localhost/api/student/complaints` | ✅ student | Submit a complaint |
| `GET` | `http://localhost/api/fees/my` | ✅ | Student fee records + summary + receipts |
| `GET` | `http://localhost/api/fees/receipts` | ✅ | Student payment receipts list |
| `GET` | `http://localhost/api/attendance/my` | ✅ | Student attendance records + monthly summary |
| `POST` | `http://localhost/api/attendance/checkin` | ✅ | Record today's check-in time |
| `POST` | `http://localhost/api/attendance/checkout` | ✅ | Record today's check-out time |
| `POST` | `http://localhost/api/complaints` | ✅ | Submit a new complaint |
| `GET` | `http://localhost/api/complaints/my` | ✅ | View own complaints with status |
| `PUT` | `http://localhost/api/auth/password` | ✅ | Change password |
| `POST` | `http://localhost/api/auth/password/send-otp` | ❌ | Send reset OTP |
| `POST` | `http://localhost/api/auth/password/verify-otp` | ❌ | Verify OTP |
| `POST` | `http://localhost/api/auth/password/reset` | ✅ reset_token | Set new password |
| `GET` | `http://localhost/api/notices` | ✅ | List all notices |
| `POST` | `http://localhost/api/admin/login` | ❌ | Admin login |
| `GET` | `http://localhost/api/admin/dashboard` | ✅ | Admin dashboard counts |
| `GET` | `http://localhost/api/admin/students` | ✅ admin | Paginated student directory; optional `search` and `per_page` |
| `GET` | `http://localhost/api/admin/students/pending` | ✅ admin | Students awaiting approval |
| `GET` | `http://localhost/api/admin/students/{id}` | ✅ admin | Student profile and room assignment |

---

## CORS Configuration

Cross-Origin Resource Sharing (CORS) is configured to allow Android app connections from any origin.

| Setting | Value | Description |
|---------|-------|-------------|
| `paths` | `api/*`, `sanctum/csrf-cookie` | Which routes get CORS headers |
| `allowed_origins` | `*` | Any origin is allowed |
| `allowed_methods` | `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS` | HTTP methods permitted |
| `allowed_headers` | `Content-Type`, `X-Requested-With`, `Authorization`, `X-XSRF-TOKEN` | Headers the client may send |
| `supports_credentials` | `false` | Cookies are not shared cross-origin |

The `HandleCors` middleware is registered globally in the framework and reads from `config/cors.php`. Preflight `OPTIONS` requests are handled automatically.

> For production, replace `allowed_origins` with your actual domain(s) instead of `*`.

---

## Testing with Postman

1. Set base URL to `http://localhost`
2. Add header: `Accept: application/json` on all requests
3. After login, copy the `token` value
4. On protected requests, set Authorization → **Bearer Token** → paste the token

---

*Generated for Manmohan Memorial Polytechnic — Hostel Management System v2*
