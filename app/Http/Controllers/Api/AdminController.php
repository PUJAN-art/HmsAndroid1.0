<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Fee;
use App\Models\Room;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        if ($user->is_active === false) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        AuditLogger::log('login', 'Auth', $user, 'Admin mobile login');

        $user->tokens()->where('name', 'admin-mobile')->delete();
        $token = $user->createToken('admin-mobile')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful.',
            'token'   => $token,
            'admin'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Access denied. This endpoint is for admins only.',
            ], 403);
        }

        $totalStudents  = User::where('role', 'student')->count();
        $totalRooms     = Room::count();
        $totalComplaints = Complaint::count();

        $dueFeesQuery = Fee::whereIn('status', ['pending', 'overdue']);
        $dueFeesCount = (clone $dueFeesQuery)->count();
        $dueFeesAmount = (clone $dueFeesQuery)->sum('amount');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_students'   => $totalStudents,
                'total_rooms'      => $totalRooms,
                'total_complaints' => $totalComplaints,
                'due_fees'         => $dueFeesCount,
                'due_fees_amount'  => round((float) $dueFeesAmount, 2),
            ],
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $query = User::where('role', 'student')->with('bed.room');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($students) use ($search) {
                $students->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id_number', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'status' => 'success',
            'data' => $students->getCollection()->map(fn (User $student) => $this->studentResource($student))->values(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    public function pendingStudents(): JsonResponse
    {
        $students = User::where('role', 'student')
            ->where('is_approved', false)
            ->with('bed.room')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'total' => $students->count(),
            'data' => $students->map(fn (User $student) => $this->studentResource($student)),
        ]);
    }

    public function showStudent(int $id): JsonResponse
    {
        $student = User::where('role', 'student')
            ->with('bed.room')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->studentResource($student),
        ]);
    }

    private function studentResource(User $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
            'student_id_number' => $student->student_id_number,
            'admission_no' => $student->admission_no,
            'department' => $student->department,
            'year' => $student->year,
            'semester' => $student->semester,
            'is_approved' => (bool) $student->is_approved,
            'is_active' => (bool) $student->is_active,
            'bed' => $student->bed ? [
                'id' => $student->bed->id,
                'bed_number' => $student->bed->bed_number,
                'room_number' => $student->bed->room?->room_number,
            ] : null,
        ];
    }
}
