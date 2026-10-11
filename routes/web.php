<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Auth;

// Debug routes removed for security
// Route::get('/debug/login-admin', ...);
// Public Registration Form Routes
Route::get('/register-form', [\App\Http\Controllers\PublicFormController::class, 'showForm'])->name('public.registration.form');
Route::post('/register-form', [\App\Http\Controllers\PublicFormController::class, 'submitForm'])->name('public.registration.submit');
Route::get('/registration-success', [\App\Http\Controllers\PublicFormController::class, 'success'])->name('public.registration.success');

// Landing Page Route

Route::get('/', function () {
    $activeForms = \App\Models\RegistrationForm::where('is_active', true)->latest()->get();
    return view('welcome', compact('activeForms'));
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif (Auth::user()->role === 'visitor') {
             return redirect()->route('visitor.dashboard');
        }
        return redirect()->route('student.dashboard');
    })->name('dashboard');

    // Visitor Routes
    Route::middleware(['auth', 'verified', 'role:visitor'])->group(function () {
        Route::get('/visitor/dashboard', [\App\Http\Controllers\VisitorDashboardController::class, 'dashboard'])->name('visitor.dashboard');
        Route::post('/visitor/request', [\App\Http\Controllers\VisitorDashboardController::class, 'requestVisit'])->name('visitor.request');
        Route::get('/visitor/pass', [\App\Http\Controllers\VisitorDashboardController::class, 'visitorPass'])->name('visitor.pass');
        Route::get('/visitor/pass/{id}', [\App\Http\Controllers\VisitorDashboardController::class, 'showPass'])->name('visitor.pass.show');
        Route::get('/visitor/pass/{id}/download', [\App\Http\Controllers\VisitorDashboardController::class, 'downloadPass'])->name('visitor.pass.download');
        Route::get('/verify-pass/{passId}', [\App\Http\Controllers\VisitorDashboardController::class, 'verifyPass'])->name('visitor.verify-pass');
        Route::get('/visitor/profile', [\App\Http\Controllers\VisitorDashboardController::class, 'profile'])->name('visitor.profile');
        Route::get('/visitor/card-profile', [\App\Http\Controllers\VisitorDashboardController::class, 'cardProfile'])->name('visitor.card-profile');
        Route::post('/profile/photo/upload', [\App\Http\Controllers\VisitorDashboardController::class, 'uploadProfilePhoto'])->name('profile.photo.upload');
    });

    // Chatbot Route
    Route::post('/chatbot/ask', [\App\Http\Controllers\ChatbotController::class, 'ask'])->name('chatbot.ask');

    // Theme and Locale Routes
    Route::post('/theme/toggle', [\App\Http\Controllers\ThemeController::class, 'toggleTheme'])->name('theme.toggle');
    Route::post('/theme/font-size', [\App\Http\Controllers\ThemeController::class, 'changeFontSize'])->name('theme.font-size');
    Route::get('/locale/{locale}', [\App\Http\Controllers\ThemeController::class, 'changeLocale'])->name('locale.change');

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('recent');
    });

    // Search Routes
    Route::get('/admin/search', [\App\Http\Controllers\SearchController::class, 'adminSearch'])->name('admin.search')->middleware(['role:admin']);
    Route::get('/student/search', [\App\Http\Controllers\SearchController::class, 'studentSearch'])->name('student.search')->middleware(['role:student']);

    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::resource('visitors', \App\Http\Controllers\VisitorController::class)->only(['index', 'store', 'update']);
        Route::get('/fees/payments', [\App\Http\Controllers\FeeController::class, 'paymentReviews'])->name('fees.payments.index');
        Route::get('/fees/payments/{payment}/proof', [\App\Http\Controllers\FeeController::class, 'showPaymentProof'])->name('fees.payments.proof');
        Route::post('/fees/payments/{payment}/review', [\App\Http\Controllers\FeeController::class, 'reviewPayment'])->name('fees.payments.review');
        Route::resource('fees', \App\Http\Controllers\FeeController::class)->only(['index', 'show']);
        Route::resource('rooms', \App\Http\Controllers\RoomController::class);
        Route::resource('notices', \App\Http\Controllers\NoticeController::class);
        Route::resource('complaints', \App\Http\Controllers\ComplaintController::class)->only(['index', 'update']);
        
        Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/export', [App\Http\Controllers\AttendanceController::class, 'export'])->name('attendance.export');

        // Feedback Admin
        Route::get('/feedback', [\App\Http\Controllers\FeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/analytics', [\App\Http\Controllers\FeedbackController::class, 'analytics'])->name('feedback.analytics');
        Route::post('/feedback/{id}/status', [\App\Http\Controllers\FeedbackController::class, 'updateStatus'])->name('feedback.status');

        // Registration Forms Management
        Route::prefix('registration-forms')->name('registration-forms.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'store'])->name('store');
            Route::get('/{registrationForm}/edit', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'edit'])->name('edit');
            Route::put('/{registrationForm}', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'update'])->name('update');
            Route::patch('/{registrationForm}/toggle', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'toggle'])->name('toggle');
            Route::delete('/{registrationForm}', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'destroy'])->name('destroy');
            Route::get('/{registrationForm}/submissions', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'submissions'])->name('submissions');
            Route::get('/submission/{submission}', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'showSubmission'])->name('show-submission');
            Route::post('/submission/{submission}/approve', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'approveSubmission'])->name('approve-submission');
            Route::post('/submission/{submission}/reject', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'rejectSubmission'])->name('reject-submission');
            Route::delete('/submission/{submission}', [\App\Http\Controllers\Admin\RegistrationFormController::class, 'deleteSubmission'])->name('delete-submission');
        });

        // Form Submissions Management
        Route::prefix('form-submissions')->name('form-submissions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FormSubmissionController::class, 'index'])->name('index');
            Route::get('/{formSubmission}', [\App\Http\Controllers\Admin\FormSubmissionController::class, 'show'])->name('show');
            Route::post('/{formSubmission}/status', [\App\Http\Controllers\Admin\FormSubmissionController::class, 'updateStatus'])->name('updateStatus');
            Route::delete('/{formSubmission}', [\App\Http\Controllers\Admin\FormSubmissionController::class, 'destroy'])->name('destroy');
        });

        // Room Requests Admin
        Route::get('/room-requests', [\App\Http\Controllers\RoomController::class, 'adminRequests'])->name('room-requests.index');
        Route::post('/room-requests/{id}/approve', [\App\Http\Controllers\RoomController::class, 'approveRequest'])->name('room-requests.approve');
        Route::post('/room-requests/{id}/reject', [\App\Http\Controllers\RoomController::class, 'rejectRequest'])->name('room-requests.reject');


        // Approvals
        Route::get('/users/pending', [AdminController::class, 'pendingUsers'])->name('users.pending');
        Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve');
        Route::delete('/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('users.reject');

        // Admin Profile
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::post('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Student Management
        Route::get('/students', [AdminController::class, 'students'])->name('students.index');
        Route::get('/students/{id}/profile', [AdminController::class, 'showStudentProfile'])->name('students.profile');
        Route::get('/students/{id}/assign-room', [AdminController::class, 'assignRoomForm'])->name('students.assign-room');
        Route::post('/students/{id}/assign-room', [AdminController::class, 'assignRoom'])->name('students.assign-room.post');
        Route::post('/students/{id}/unassign', [AdminController::class, 'unassignRoom'])->name('students.unassign');
        Route::post('/students/{id}/toggle-status', [AdminController::class, 'toggleStudentStatus'])->name('students.toggle-status');

        // ID Card Admin
        Route::get('/students/{id}/id-card', [\App\Http\Controllers\IDCardController::class, 'show'])->name('students.id-card.show');
        Route::get('/students/{id}/id-card/edit', [\App\Http\Controllers\IDCardController::class, 'edit'])->name('students.id-card.edit');
        Route::put('/students/{id}/id-card', [\App\Http\Controllers\IDCardController::class, 'update'])->name('students.id-card.update');

        // Visitor Request Management (Specific Visits)
        Route::get('/visitor-requests', [AdminController::class, 'visitorRequests'])->name('visitors.requests');
        Route::get('/visitor-requests/{id}/edit', [AdminController::class, 'editVisitRequest'])->name('visitors.edit_request');
        Route::put('/visitor-requests/{id}', [AdminController::class, 'updateVisitRequest'])->name('visitors.update_request');

        // Visitor Profile Management
        Route::get('/visitor-profiles', [AdminController::class, 'visitorProfiles'])->name('visitors.profiles');
        Route::put('/visitors/{id}/pass', [AdminController::class, 'updateVisitorPass'])->name('visitors.update-pass');
        Route::post('/visitors/{id}/toggle-status', [AdminController::class, 'toggleVisitorStatus'])->name('visitors.toggle-status');

        // Activity Logs
        Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs.index');

        // Messages to Students
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/students/{studentId}/send', [\App\Http\Controllers\MessageController::class, 'sendForm'])->name('send-form');
            Route::post('/send', [\App\Http\Controllers\MessageController::class, 'store'])->name('store');
            Route::get('/inbox', [\App\Http\Controllers\MessageController::class, 'adminInbox'])->name('inbox');
            Route::get('/sent', [\App\Http\Controllers\MessageController::class, 'adminSentMessages'])->name('sent');
            Route::get('/{id}', [\App\Http\Controllers\MessageController::class, 'adminShowMessage'])->name('show');
            Route::post('/{id}/reply', [\App\Http\Controllers\MessageController::class, 'adminStoreReply'])->name('store-reply');
        });

        // Admin Settings
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings');
        Route::get('/settings/edit', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings/update', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    });

    Route::prefix('student')->name('student.')->middleware(['role:student', 'App\Http\Middleware\CheckStudentActive'])->group(function () {
        Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
        
        // Profile
        Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
        Route::post('/profile', [StudentController::class, 'updateProfile'])->name('profile.update');

        // Room Browsing and Requests
        Route::get('/rooms/browse', [\App\Http\Controllers\RoomController::class, 'browse'])->name('rooms.browse');
        Route::get('/rooms/{id}', [\App\Http\Controllers\RoomController::class, 'showRoom'])->name('rooms.show');
        Route::post('/rooms/request', [\App\Http\Controllers\RoomController::class, 'requestRoom'])->name('rooms.request');
        Route::get('/room-requests', [\App\Http\Controllers\RoomController::class, 'myRequests'])->name('room-requests');

        // Detailed Views
        Route::get('/room', [StudentController::class, 'room'])->name('room');
        Route::get('/fees', [StudentController::class, 'fees'])->name('fees');
        Route::post('/fees/payments', [\App\Http\Controllers\FeeController::class, 'submitPayment'])->name('fees.payments.store');
        Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'studentIndex'])->name('attendance');

        // ID Card
        Route::get('/id-card', [\App\Http\Controllers\IDCardController::class, 'show'])->name('id-card');
        Route::get('/id-card/download', [\App\Http\Controllers\IDCardController::class, 'download'])->name('id-card.download');

        // Complaints
        Route::resource('complaints', \App\Http\Controllers\ComplaintController::class)->only(['create', 'store', 'index']);

        // Visitor visit request management removed - admin-only approval
        
        // Visit Requests from Visitors (Student Review)
        Route::get('/visit-requests', [StudentController::class, 'listVisitRequests'])->name('visit-requests.index');
        Route::post('/visit-requests/{id}/accept', [StudentController::class, 'acceptVisitRequest'])->name('visit-requests.accept');
        Route::post('/visit-requests/{id}/reject', [StudentController::class, 'rejectVisitRequest'])->name('visit-requests.reject');

        // Feedback (Now strictly for students)
        Route::get('/feedback/create', [\App\Http\Controllers\FeedbackController::class, 'create'])->name('feedback.create');
        Route::post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

        // Activity Logs
        Route::get('/activity-logs', [StudentController::class, 'activityLogs'])->name('activity-logs.index');

        // Messages (Inbox)
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/inbox', [\App\Http\Controllers\MessageController::class, 'inbox'])->name('inbox');
            Route::get('/{id}', [\App\Http\Controllers\MessageController::class, 'show'])->name('show');
            Route::delete('/{id}', [\App\Http\Controllers\MessageController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/reply', [\App\Http\Controllers\MessageController::class, 'replyForm'])->name('reply-form');
            Route::post('/{id}/reply', [\App\Http\Controllers\MessageController::class, 'storeReply'])->name('store-reply');
            Route::get('/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('unread-count');
        });
    });

    // Password Update for Authenticated Users (Secure Unique Route)
    Route::post('/user/security/update-password', [\App\Http\Controllers\AuthController::class, 'updatePassword'])->name('user.password.secure_update');

    // Activity Log Deletion
    Route::post('/activity-logs/clear', [AdminController::class, 'clearActivityLogs'])->name('activity-logs.clear')->middleware('role:admin');
    Route::post('/student/activity-logs/clear', [StudentController::class, 'clearActivityLogs'])->name('student.activity-logs.clear')->middleware('role:student');
});

require __DIR__.'/auth.php'; // Standard laravel auth include, though file might not exist yet
