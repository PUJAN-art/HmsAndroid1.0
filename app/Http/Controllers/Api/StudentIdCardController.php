<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentIdCardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()->loadMissing('bed.room');
        $room = $student->bed?->room;
        $expiryDate = $student->id_card_expiry_date;

        return response()->json([
            'status' => 'success',
            'data' => [
                'card_title' => 'Student ID Card',
                'institution_name' => config('app.name'),
                'logo_url' => asset('images/logo.jpg'),
                'role' => strtoupper($student->role),
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id_number' => $student->student_id_number
                        ?: 'HMS-' . str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                    'photo_url' => $student->profile_photo_path
                        ? asset('storage/' . $student->profile_photo_path)
                        : null,
                    'department' => $student->department,
                    'year' => $student->year,
                    'semester' => $student->semester,
                    'blood_group' => $student->blood_group ?: 'N/A',
                    'issued_on' => $student->id_card_issued_date,
                    'expires_on' => $expiryDate,
                    'expiry_label' => $expiryDate
                        ? Carbon::parse($expiryDate)->format('M Y')
                        : 'Valid till Graduation',
                    'phone' => $student->phone ?: 'N/A',
                    'emergency_contact_name' => $student->emergency_contact_name ?: 'Parent',
                    'emergency_contact' => $student->emergency_contact ?: $student->phone,
                ],
                'room' => $room ? [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'type' => $room->type,
                    'status' => $room->status,
                    'capacity' => $room->capacity,
                    'bed_id' => $student->bed->id,
                    'bed_number' => $student->bed->bed_number,
                ] : null,
                'qr_code_payload' => route('admin.students.profile', $student->id),
            ],
        ]);
    }
}