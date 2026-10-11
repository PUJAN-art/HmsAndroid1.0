<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class MobileAuthController extends Controller
{
    // ----------------------------------------------------------------
    // POST /api/auth/register
    // ----------------------------------------------------------------
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'          => ['required', 'confirmed', Rules\Password::defaults()],
            'phone'             => ['required', 'string', 'max:20'],
            'role'              => ['required', 'in:student,visitor'],
            // Student-specific
            'parent_phone'      => ['required_if:role,student', 'nullable', 'string', 'max:20'],
            'year'              => ['required_if:role,student', 'nullable', 'integer', 'between:1,4'],
            'department'        => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'semester'          => ['required_if:role,student', 'nullable', 'integer', 'between:1,8'],
            'address'           => ['required_if:role,student', 'nullable', 'string', 'max:255'],
            'student_id_number' => ['required_if:role,student', 'nullable', 'string', 'max:50', 'unique:users'],
        ]);

        $userData = [
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'phone'             => $validated['phone'],
            'parent_phone'      => $validated['role'] === 'student' ? ($validated['parent_phone'] ?? null) : null,
            'year'              => $validated['role'] === 'student' ? ($validated['year'] ?? null) : null,
            'department'        => $validated['role'] === 'student' ? ($validated['department'] ?? null) : null,
            'semester'          => $validated['role'] === 'student' ? ($validated['semester'] ?? null) : null,
            'address'           => $validated['address'] ?? null,
            'student_id_number' => $validated['role'] === 'student' ? ($validated['student_id_number'] ?? null) : null,
        ];

        if ($validated['role'] === 'visitor') {
            $userData['student_approved'] = false;
            $userData['admin_approved']   = false;
            $userData['is_approved']      = false;
        } else {
            $userData['is_approved'] = false;
        }

        $user = User::create($userData);

        if ($user->role === 'visitor') {
            app(NotificationService::class)->notifyNewVisitorRegistration($user->id);
        }

        return response()->json([
            'message' => 'Registration successful. Please wait for admin approval before logging in.',
        ], 201);
    }

    // ----------------------------------------------------------------
    // POST /api/auth/login
    // ----------------------------------------------------------------
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Approval checks
        if ($user->role === 'visitor' && ! $user->admin_approved) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account is pending admin approval.',
            ], 403);
        }

        if ($user->role === 'student' && ! $user->is_approved) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account is pending admin approval.',
            ], 403);
        }

        if ($user->is_active === false) {
            Auth::logout();
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        AuditLogger::log('login', 'Auth', $user, 'Mobile login');

        // Revoke old mobile tokens and issue a fresh one
        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    // ----------------------------------------------------------------
    // POST /api/student/register
    // Student self-registration. Returns a Bearer token immediately
    // so the app can show a "pending approval" screen without a
    // separate login step. Token is valid but protected routes will
    // still reject the student until admin approves the account.
    // ----------------------------------------------------------------
    public function studentRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact'      => ['required', 'string', 'max:20'],
            'gender'       => ['required', 'in:male,female,other'],
            'address'      => ['required', 'string', 'max:500'],
            'admission_no' => ['required', 'string', 'max:50', 'unique:users,admission_no'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'phone'        => $validated['contact'],
            'gender'       => $validated['gender'],
            'address'      => $validated['address'],
            'admission_no' => $validated['admission_no'],
            'password'     => Hash::make($validated['password']),
            'role'         => 'student',
            'is_approved'  => false,   // requires admin approval before login
            'is_active'    => true,
        ]);

        AuditLogger::log('register', 'Auth', $user, 'Student mobile registration');

        // Issue token immediately so the app receives it right away.
        // The student cannot access protected data until is_approved = true.
        $token = $user->createToken('student-mobile')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Registration successful. Please wait for admin approval before you can log in.',
            'token'   => $token,
            'student' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'contact'      => $user->phone,
                'gender'       => $user->gender,
                'address'      => $user->address,
                'admission_no' => $user->admission_no,
                'is_approved'  => $user->is_approved,
                'is_active'    => $user->is_active,
            ],
        ], 201);
    }

    // ----------------------------------------------------------------
    // POST /api/student/login
    // Student-specific login — rejects non-student accounts at the
    // role level before any token is issued.
    // ----------------------------------------------------------------
    public function studentLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 1. Verify the account exists and the password is correct
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // 2. Ensure the account belongs to a student — reject admin/visitor silently
        if ($user->role !== 'student') {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // 3. Approval gate — admin must have approved the registration
        if (! $user->is_approved) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account is pending admin approval.',
            ], 403);
        }

        // 4. Active gate — admin may deactivate a student
        if ($user->is_active === false) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        AuditLogger::log('login', 'Auth', $user, 'Student mobile login');

        // 5. Revoke any existing student-mobile tokens then issue a fresh one
        $user->tokens()->where('name', 'student-mobile')->delete();
        $token = $user->createToken('student-mobile')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful.',
            'token'   => $token,
            'student' => $this->studentResource($user),
        ]);
    }

    // ----------------------------------------------------------------
    // GET /api/student/profile   (requires auth:sanctum)
    // Returns the authenticated student's full profile including
    // assigned room/bed, pending fees summary, and attendance count.
    // Rejects non-student tokens with 403.
    // ----------------------------------------------------------------
    public function studentProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Ensure only students can access this endpoint
        if ($user->role !== 'student') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Access denied. This endpoint is for students only.',
            ], 403);
        }

        // Eagerly load bed → room to avoid N+1
        $user->loadMissing('bed.room');

        // Pending fees summary (count + total amount)
        $pendingFees = $user->fees()
            ->where('status', 'pending')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total')
            ->first();

        // Attendance this month
        $attendanceThisMonth = \App\Models\Attendance::where('student_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('status', 'present')
            ->count();

        // Room info — null if no bed assigned yet
        $roomInfo = null;
        if ($user->bed && $user->bed->room) {
            $room    = $user->bed->room;
            $roomInfo = [
                'room_id'         => $room->id,
                'room_number'     => $room->room_number,
                'room_type'       => $room->type,
                'room_status'     => $room->status,
                'room_photo_url'  => $room->room_photo
                                        ? asset('storage/' . $room->room_photo)
                                        : null,
                'capacity'        => $room->capacity,
                'bed_id'          => $user->bed->id,
                'bed_number'      => $user->bed->bed_number,
            ];
        }

        return response()->json([
            'status'  => 'success',
            'student' => [
                // Personal info
                'id'                   => $user->id,
                'name'                 => $user->name,
                'email'                => $user->email,
                'contact'              => $user->phone,
                'gender'               => $user->gender,
                'address'              => $user->address,
                'admission_no'         => $user->admission_no,
                'student_id_number'    => $user->student_id_number,
                'profile_photo_url'    => $user->profile_photo_path
                                            ? asset('storage/' . $user->profile_photo_path)
                                            : null,

                // Academic info
                'department'           => $user->department,
                'year'                 => $user->year,
                'semester'             => $user->semester,

                // Medical / ID card
                'blood_group'          => $user->blood_group,
                'emergency_contact'    => $user->emergency_contact,
                'emergency_contact_name' => $user->emergency_contact_name,
                'id_card_expiry_date'  => $user->id_card_expiry_date,

                // Account status
                'is_approved'          => (bool) $user->is_approved,
                'is_active'            => (bool) $user->is_active,

                // Room assignment
                'room'                 => $roomInfo,

                // Fee summary
                'pending_fees' => [
                    'count'  => (int) ($pendingFees->count ?? 0),
                    'total'  => (float) ($pendingFees->total ?? 0),
                ],

                // Attendance
                'attendance_this_month' => $attendanceThisMonth,
            ],
        ]);
    }

    // ----------------------------------------------------------------
    // POST /api/student/profile (requires auth:sanctum, students only)
    // Updates the same editable fields as the student web profile form.
    // ----------------------------------------------------------------
    public function updateStudentProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile_photo' => ['sometimes', 'nullable', 'image', 'max:1024'],
        ]);

        $user = $request->user();
        $data = collect($validated)->only(['phone', 'address'])->all();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        if ($data !== []) {
            $user->update($data);
        }

        $user->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'student' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'contact' => $user->phone,
                'address' => $user->address,
                'profile_photo_url' => $user->profile_photo_path
                    ? asset('storage/'.$user->profile_photo_path)
                    : null,
            ],
        ]);
    }

    // ----------------------------------------------------------------
    // POST /api/auth/logout   (requires auth:sanctum)
    // ----------------------------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        AuditLogger::log('logout', 'Auth', $user, 'Mobile logout');

        // Revoke only the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ----------------------------------------------------------------
    // GET /api/auth/me   (requires auth:sanctum)
    // ----------------------------------------------------------------
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userResource($request->user()));
    }

    // ----------------------------------------------------------------
    // OTP PASSWORD RESET — Step 1: send OTP
    // POST /api/auth/password/send-otp
    // ----------------------------------------------------------------
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // Return generic message to prevent email enumeration
            return response()->json(['message' => 'If that email exists, an OTP has been sent.']);
        }

        $otpRecord = PasswordOtp::generateOtp($user->id);

        try {
            Mail::to($user->email)->send(
                new \App\Mail\SendOtpMail($otpRecord->otp_code, $user->name)
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        return response()->json(['message' => 'OTP sent to your email address.']);
    }

    // ----------------------------------------------------------------
    // OTP PASSWORD RESET — Step 2: verify OTP
    // POST /api/auth/password/verify-otp
    // ----------------------------------------------------------------
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! PasswordOtp::verifyOtp($user->id, $request->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        // Issue a short-lived reset token
        $resetToken = $user->createToken('password-reset', ['password-reset'], now()->addMinutes(15))->plainTextToken;

        return response()->json([
            'message'      => 'OTP verified.',
            'reset_token'  => $resetToken,
        ]);
    }

    // ----------------------------------------------------------------
    // OTP PASSWORD RESET — Step 3: reset password
    // POST /api/auth/password/reset   (requires auth:sanctum with ability password-reset)
    // ----------------------------------------------------------------
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->currentAccessToken()->can('password-reset')) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 403);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Clean up OTPs and the reset token
        PasswordOtp::where('user_id', $user->id)->delete();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Password reset successfully. You can now log in.']);
    }

    // ----------------------------------------------------------------
    // PUT /api/auth/password   (requires auth:sanctum — change own password)
    // ----------------------------------------------------------------
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------
    private function userResource(User $user): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'role'               => $user->role,
            'phone'              => $user->phone,
            'department'         => $user->department,
            'year'               => $user->year,
            'semester'           => $user->semester,
            'student_id_number'  => $user->student_id_number,
            'is_approved'        => $user->is_approved,
            'is_active'          => $user->is_active,
            'profile_photo_path' => $user->profile_photo_path,
            'blood_group'        => $user->blood_group,
            'bed_id'             => $user->bed_id,
            'locale'             => $user->locale,
            'theme'              => $user->theme,
        ];
    }

    private function studentResource(User $user): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'phone'              => $user->phone,
            'student_id_number'  => $user->student_id_number,
            'department'         => $user->department,
            'year'               => $user->year,
            'semester'           => $user->semester,
            'blood_group'        => $user->blood_group,
            'address'            => $user->address,
            'profile_photo_url'  => $user->profile_photo_path
                                        ? asset('storage/' . $user->profile_photo_path)
                                        : null,
            'bed'                => $user->bed ? [
                'bed_id'        => $user->bed->id,
                'bed_number'    => $user->bed->bed_number,
                'room_number'   => $user->bed->room->room_number ?? null,
                'room_type'     => $user->bed->room->type ?? null,
            ] : null,
            'id_card_expiry_date' => $user->id_card_expiry_date,
            'is_active'           => $user->is_active,
        ];
    }
}
