<?php

use App\Http\Controllers\Api\AdminController as ApiAdminController;
use App\Http\Controllers\Api\AttendanceController as ApiAttendanceController;
use App\Http\Controllers\Api\ComplaintController as ApiComplaintController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\FeedbackController as ApiFeedbackController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\NoticeController as ApiNoticeController;
use App\Http\Controllers\Api\VisitorController as ApiVisitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes (Sanctum token-based auth)
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api (set in bootstrap/app.php).
| Protected routes require the Bearer token returned by POST /api/auth/login.
|
| Authentication flow for the mobile app:
|   1. POST /api/auth/login          → receive token
|   2. Send  Authorization: Bearer {token} on every authenticated request
|   3. POST /api/auth/logout         → revoke token
|
| Password reset flow:
|   1. POST /api/auth/password/send-otp   → OTP emailed to user
|   2. POST /api/auth/password/verify-otp → verify OTP, receive reset_token
|   3. POST /api/auth/password/reset      → supply reset_token + new password
|
*/

// ── Health check (no token required) ─────────────────────────────────────

Route::get('test', function () {
    return response()->json([
        'status'  => 'success',
        'message' => 'API working',
    ]);
});

// ── Public endpoints (no token required) ─────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',                [MobileAuthController::class, 'register']);
    Route::post('login',                   [MobileAuthController::class, 'login']);
    Route::post('password/send-otp',       [MobileAuthController::class, 'sendOtp']);
    Route::post('password/verify-otp',     [MobileAuthController::class, 'verifyOtp']);
});

// ── Admin-specific endpoints ──────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    // POST /api/admin/login — admins only, returns Bearer token + admin info
    Route::post('login',                   [ApiAdminController::class, 'login']);
});

// ── Student-specific endpoints ────────────────────────────────────────────
Route::prefix('student')->group(function () {
    // POST /api/student/register — students only, returns Bearer token + student info
    Route::post('register',                [MobileAuthController::class, 'studentRegister']);
    // POST /api/student/login — students only, returns Bearer token + student info
    Route::post('login',                   [MobileAuthController::class, 'studentLogin']);
});

// ── Room endpoints (public — no token required) ───────────────────────────
Route::prefix('rooms')->group(function () {
    Route::get('/',           [RoomController::class, 'index']);      // GET /api/rooms
    Route::get('available',   [RoomController::class, 'available']);  // GET /api/rooms/available
    Route::get('{id}',        [RoomController::class, 'show']);       // GET /api/rooms/{id}
});

// ── Protected endpoints (token required) ─────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout',             [MobileAuthController::class, 'logout']);
    Route::get('auth/me',                  [MobileAuthController::class, 'me']);
    Route::post('auth/password',           [MobileAuthController::class, 'updatePassword']);
    Route::put('auth/password',            [MobileAuthController::class, 'updatePassword']);

    // Password reset — requires the special reset_token from verify-otp step
    Route::post('auth/password/reset',     [MobileAuthController::class, 'resetPassword']);

    // ── Student protected endpoints ───────────────────────────────────────
    Route::prefix('student')->middleware('role:student')->group(function () {
        Route::get('profile',              [MobileAuthController::class, 'studentProfile']);
        Route::post('profile',             [MobileAuthController::class, 'updateStudentProfile']);
        Route::get('id-card',              [\App\Http\Controllers\Api\StudentIdCardController::class, 'show']);
        Route::get('rooms',                [RoomController::class, 'index']);
        Route::get('rooms/available',       [RoomController::class, 'available']);
        Route::get('rooms/{id}',            [RoomController::class, 'show']);
        Route::post('rooms/{id}/request',   [\App\Http\Controllers\Api\RoomRequestController::class, 'storeForRoom']);
        Route::get('room-requests',         [\App\Http\Controllers\Api\RoomRequestController::class, 'index']);
        Route::post('room-requests',        [\App\Http\Controllers\Api\RoomRequestController::class, 'store']);
        Route::get('room-requests/{id}',    [\App\Http\Controllers\Api\RoomRequestController::class, 'show']);
        Route::delete('room-requests/{id}', [\App\Http\Controllers\Api\RoomRequestController::class, 'cancel']);
        Route::get('fees',                  [FeeController::class, 'myFees']);
        Route::get('fees/receipts',         [FeeController::class, 'myReceipts']);
        Route::get('fees/payments/{payment}/proof', [FeeController::class, 'showPaymentProof'])->name('api.student.fees.payments.proof');
        Route::post('fees/{fee}/payments',  [FeeController::class, 'submitPayment']);
        Route::get('activity-logs', [\App\Http\Controllers\Api\ActivityLogController::class, 'index']);
        Route::prefix('messages')->group(function () {
            Route::get('inbox', [\App\Http\Controllers\Api\MessageController::class, 'inbox']);
            Route::get('unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount']);
            Route::get('{id}', [\App\Http\Controllers\Api\MessageController::class, 'show'])->whereNumber('id');
            Route::post('{id}/reply', [\App\Http\Controllers\Api\MessageController::class, 'reply'])->whereNumber('id');
        });
        Route::get('attendance',            [ApiAttendanceController::class, 'myAttendance']);
        Route::post('attendance/checkin',   [ApiAttendanceController::class, 'checkIn']);
        Route::post('attendance/checkout',  [ApiAttendanceController::class, 'checkOut']);
        Route::get('complaints',            [ApiComplaintController::class, 'myComplaints']);
        Route::post('complaints',           [ApiComplaintController::class, 'store']);
        Route::post('feedback',             [ApiFeedbackController::class, 'store']);
        Route::get('visitors',              [ApiVisitorController::class, 'myVisitors']);
        Route::post('visitors',             [ApiVisitorController::class, 'store']);
        Route::get('notices',               [ApiNoticeController::class, 'index']);
    });

    // ── Fee endpoints (student only) ──────────────────────────────────────
    Route::prefix('fees')->group(function () {
        Route::get('my',       [FeeController::class, 'myFees']);      // GET /api/fees/my
        Route::get('receipts', [FeeController::class, 'myReceipts']);  // GET /api/fees/receipts
        Route::post('{fee}/payments', [FeeController::class, 'submitPayment'])
            ->middleware('role:student'); // POST /api/fees/{fee}/payments
    });

    Route::middleware('role:student')->group(function () {
        Route::post('room-requests', [\App\Http\Controllers\Api\RoomRequestController::class, 'store']);
    });

    // ── Attendance endpoints (student only) ───────────────────────────────
    Route::prefix('attendance')->group(function () {
        Route::get('my',       [ApiAttendanceController::class, 'myAttendance']); // GET  /api/attendance/my
        Route::post('checkin', [ApiAttendanceController::class, 'checkIn']);      // POST /api/attendance/checkin
        Route::post('checkout',[ApiAttendanceController::class, 'checkOut']);     // POST /api/attendance/checkout
    });

    // ── Complaint endpoints (student only) ────────────────────────────────
    Route::prefix('complaints')->group(function () {
        Route::post('/',   [ApiComplaintController::class, 'store']);        // POST /api/complaints
        Route::get('my',   [ApiComplaintController::class, 'myComplaints']); // GET  /api/complaints/my
    });

    // ── Visitor endpoints (student only) ──────────────────────────────────
    Route::prefix('visitors')->group(function () {
        Route::post('/',  [ApiVisitorController::class, 'store']);       // POST /api/visitors
        Route::get('my',  [ApiVisitorController::class, 'myVisitors']);  // GET  /api/visitors/my
    });

    // ── Notice endpoints (any authenticated user) ──────────────────────────
    Route::prefix('notices')->group(function () {
        Route::get('/',   [ApiNoticeController::class, 'index']);        // GET /api/notices
    });

    // ── Admin protected endpoints ──────────────────────────────────────────
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('dashboard',            [ApiAdminController::class, 'dashboard']); // GET /api/admin/dashboard
        Route::get('students/pending',     [ApiAdminController::class, 'pendingStudents']);
        Route::get('students',             [ApiAdminController::class, 'students']);
        Route::get('students/{id}',        [ApiAdminController::class, 'showStudent']);
    });
});
