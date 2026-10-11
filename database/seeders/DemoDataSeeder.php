<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Bed;
use App\Models\Complaint;
use App\Models\Fee;
use App\Models\Feedback;
use App\Models\FormSubmission;
use App\Models\Leave;
use App\Models\Message;
use App\Models\Notice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\RegistrationForm;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            if ($this->command) {
                $this->command->warn('Demo data seeding is restricted to local and testing environments.');
            }

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@hostel.com'],
            [
                'name' => 'Demo Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '9800000001',
                'is_approved' => true,
                'is_active' => true,
            ]
        );

        $student = User::updateOrCreate(
            ['email' => 'john@hostel.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '9800000002',
                'parent_phone' => '9800000003',
                'address' => 'Demo Hostel Road, Kathmandu',
                'gender' => 'male',
                'department' => 'Computer',
                'year' => 2,
                'semester' => 4,
                'student_id_number' => 'DEMO-0001',
                'admission_no' => 'DEMO-ADM-0001',
                'is_approved' => true,
                'student_approved' => true,
                'admin_approved' => true,
                'is_active' => true,
                'id_card_issued_date' => '2026-01-01',
                'id_card_expiry_date' => '2027-12-31',
                'blood_group' => 'B+',
                'emergency_contact' => '9800000003',
                'emergency_contact_name' => 'Demo Parent',
            ]
        );

        $pendingStudent = User::updateOrCreate(
            ['email' => 'pending@hostel.com'],
            [
                'name' => 'Pending Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '9800000010',
                'gender' => 'female',
                'department' => 'Civil',
                'year' => 1,
                'semester' => 1,
                'student_id_number' => 'DEMO-0002',
                'admission_no' => 'DEMO-ADM-0002',
                'is_approved' => false,
                'student_approved' => false,
                'admin_approved' => false,
                'is_active' => true,
            ]
        );

        $requestStudent = User::updateOrCreate(
            ['email' => 'requests@hostel.com'],
            [
                'name' => 'Room Request Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '9800000011',
                'gender' => 'female',
                'department' => 'Civil',
                'year' => 2,
                'semester' => 3,
                'student_id_number' => 'DEMO-0003',
                'admission_no' => 'DEMO-ADM-0003',
                'is_approved' => true,
                'student_approved' => true,
                'admin_approved' => true,
                'is_active' => true,
                'bed_id' => null,
            ]
        );

        $unassignedStudent = User::updateOrCreate(
            ['email' => 'unassigned@hostel.com'],
            [
                'name' => 'Unassigned Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '9800000012',
                'gender' => 'male',
                'department' => 'Electrical',
                'year' => 1,
                'semester' => 1,
                'student_id_number' => 'DEMO-0004',
                'admission_no' => 'DEMO-ADM-0004',
                'is_approved' => true,
                'student_approved' => true,
                'admin_approved' => true,
                'is_active' => true,
                'bed_id' => null,
            ]
        );

        $visitorUser = User::updateOrCreate(
            ['email' => 'visitor@hostel.com'],
            [
                'name' => 'Demo Visitor',
                'password' => Hash::make('password'),
                'role' => 'visitor',
                'phone' => '9800000020',
                'is_approved' => true,
                'student_approved' => true,
                'admin_approved' => true,
                'is_active' => true,
            ]
        );

        $rooms = [];
        foreach ([
            ['room_number' => '101', 'capacity' => 2, 'type' => 'standard', 'status' => 'active'],
            ['room_number' => '102', 'capacity' => 3, 'type' => 'deluxe', 'status' => 'active'],
            ['room_number' => '201', 'capacity' => 2, 'type' => 'standard', 'status' => 'maintenance'],
        ] as $roomData) {
            $room = Room::updateOrCreate(
                ['room_number' => $roomData['room_number']],
                $roomData
            );
            $rooms[$room->room_number] = $room;

            for ($bedNumber = 1; $bedNumber <= $room->capacity; $bedNumber++) {
                Bed::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'bed_number' => $room->room_number . '-' . $bedNumber,
                    ],
                    ['is_occupied' => false]
                );
            }
        }

        $studentBed = Bed::where('room_id', $rooms['101']->id)
            ->where('bed_number', '101-1')
            ->firstOrFail();
        $studentBed->update(['is_occupied' => true]);
        $student->update(['bed_id' => $studentBed->id]);
        $pendingStudent->update(['bed_id' => null]);

        RoomRequest::updateOrCreate(
            ['user_id' => $student->id, 'student_message' => 'Demo approved room request'],
            [
                'room_id' => $rooms['101']->id,
                'bed_id' => $studentBed->id,
                'status' => 'approved',
                'admin_notes' => 'Demo request approved.',
                'processed_at' => now()->subDays(5),
                'processed_by' => $admin->id,
            ]
        );

        RoomRequest::updateOrCreate(
            ['user_id' => $requestStudent->id, 'student_message' => 'Demo pending room request'],
            [
                'room_id' => $rooms['102']->id,
                'bed_id' => null,
                'status' => 'pending',
                'admin_notes' => null,
                'processed_at' => null,
                'processed_by' => null,
            ]
        );

        $paidFee = Fee::updateOrCreate(
            ['student_id' => $student->id, 'type' => 'monthly', 'due_date' => '2026-08-01'],
            ['amount' => 3500, 'status' => 'paid']
        );
        Fee::updateOrCreate(
            ['student_id' => $student->id, 'type' => 'monthly', 'due_date' => '2026-10-20'],
            ['amount' => 3500, 'status' => 'pending']
        );
        Fee::updateOrCreate(
            ['student_id' => $student->id, 'type' => 'mess', 'due_date' => '2026-09-15'],
            ['amount' => 1200, 'status' => 'overdue']
        );
        Payment::updateOrCreate(
            ['transaction_id' => 'DEMO-PAYMENT-0001'],
            [
                'fee_id' => $paidFee->id,
                'amount' => 3500,
                'date' => '2026-08-01',
                'method' => 'online',
            ]
        );

        foreach ([
            ['date' => now()->startOfMonth()->addDays(1)->toDateString(), 'status' => 'present', 'time_in' => '08:15:00', 'time_out' => '17:00:00'],
            ['date' => now()->startOfMonth()->addDays(2)->toDateString(), 'status' => 'late', 'time_in' => '10:15:00', 'time_out' => '17:05:00'],
            ['date' => now()->startOfMonth()->addDays(3)->toDateString(), 'status' => 'absent', 'time_in' => null, 'time_out' => null],
        ] as $attendance) {
            Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => $attendance['date']],
                $attendance
            );
        }

        Complaint::updateOrCreate(
            ['student_id' => $student->id, 'category' => 'plumbing', 'description' => 'Demo: bathroom tap is leaking.'],
            ['status' => 'pending', 'admin_remark' => null]
        );
        Complaint::updateOrCreate(
            ['student_id' => $student->id, 'category' => 'electricity', 'description' => 'Demo: study-room light was replaced.'],
            ['status' => 'resolved', 'admin_remark' => 'Demo: bulb replaced.']
        );

        Feedback::updateOrCreate(
            ['user_id' => $student->id, 'comments' => 'Demo: please extend study-room hours.'],
            [
                'room_rating' => 4,
                'mess_rating' => 5,
                'security_rating' => 4,
                'staff_rating' => 4,
                'is_anonymous' => false,
                'status' => 'pending',
                'admin_response' => null,
                'reviewed_at' => null,
            ]
        );
        Feedback::updateOrCreate(
            ['user_id' => null, 'comments' => 'Demo anonymous feedback.'],
            [
                'room_rating' => 3,
                'mess_rating' => 4,
                'security_rating' => 5,
                'staff_rating' => null,
                'is_anonymous' => true,
                'status' => 'reviewed',
                'admin_response' => 'Demo response.',
                'reviewed_at' => now()->subDays(2),
            ]
        );

        Visitor::updateOrCreate(
            ['student_id' => $student->id, 'visitor_name' => 'Demo Pending Visitor', 'visit_date' => '2026-10-20'],
            [
                'user_id' => $visitorUser->id,
                'phone' => '9800000030',
                'purpose' => 'Family visit demo request.',
                'entry_time' => null,
                'exit_time' => null,
                'status' => 'pending',
            ]
        );
        Visitor::updateOrCreate(
            ['student_id' => $student->id, 'visitor_name' => 'Demo Approved Visitor', 'visit_date' => '2026-09-20'],
            [
                'user_id' => $visitorUser->id,
                'phone' => '9800000031',
                'purpose' => 'Family visit demo history.',
                'entry_time' => '2026-09-20 10:00:00',
                'exit_time' => '2026-09-20 11:00:00',
                'status' => 'approved',
            ]
        );

        foreach ([
            ['title' => 'Demo: Hostel Orientation', 'audience' => 'all'],
            ['title' => 'Demo: Student Study Hours', 'audience' => 'students'],
        ] as $noticeData) {
            Notice::updateOrCreate(
                ['title' => $noticeData['title']],
                [
                    'user_id' => $admin->id,
                    'content' => 'This notice is sample content for testing the mobile and web notices pages.',
                    'audience' => $noticeData['audience'],
                ]
            );
        }

        Message::updateOrCreate(
            ['from_user_id' => $admin->id, 'to_user_id' => $student->id, 'subject' => 'Demo: Welcome to the hostel'],
            ['message' => 'Welcome. Contact the administration if you need help.', 'is_read' => false, 'read_at' => null]
        );
        $reply = Message::updateOrCreate(
            ['from_user_id' => $student->id, 'to_user_id' => $admin->id, 'subject' => 'Demo: Welcome to the hostel reply'],
            ['message' => 'Thank you. I have received the information.', 'is_read' => true, 'read_at' => now()]
        );
        $originalMessage = Message::where('from_user_id', $admin->id)
            ->where('to_user_id', $student->id)
            ->where('subject', 'Demo: Welcome to the hostel')
            ->first();
        $reply->update(['reply_to_id' => $originalMessage?->id]);

        Notification::updateOrCreate(
            ['user_id' => $student->id, 'title' => 'Demo: Fee due soon'],
            [
                'type' => 'warning',
                'message' => 'A demo fee is due soon.',
                'data' => ['fee_id' => $paidFee->id],
                'action_url' => route('student.fees'),
                'read_at' => null,
            ]
        );
        Notification::updateOrCreate(
            ['user_id' => $student->id, 'title' => 'Demo: Account approved'],
            [
                'type' => 'success',
                'message' => 'Your demo student account is approved.',
                'data' => null,
                'action_url' => route('student.dashboard'),
                'read_at' => now()->subDay(),
            ]
        );

        Leave::updateOrCreate(
            ['student_id' => $student->id, 'start_date' => '2026-10-18', 'end_date' => '2026-10-20'],
            ['reason' => 'Demo family visit.', 'status' => 'pending', 'admin_remark' => null]
        );
        Leave::updateOrCreate(
            ['student_id' => $student->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-12'],
            ['reason' => 'Demo approved leave history.', 'status' => 'approved', 'admin_remark' => 'Demo approved.']
        );

        $form = RegistrationForm::updateOrCreate(
            ['title' => 'Demo Student Registration'],
            [
                'description' => 'Sample active registration form for testing.',
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2099-12-31 23:59:59',
                'is_active' => true,
            ]
        );
        FormSubmission::updateOrCreate(
            ['registration_form_id' => $form->id, 'email' => 'applicant@example.test'],
            [
                'student_number' => 'DEMO-APP-001',
                'student_id_no' => 'DEMO-ID-001',
                'student_name' => 'Demo Applicant',
                'parent_name' => 'Demo Guardian',
                'parent_phone' => '9800000040',
                'phone_number' => '9800000041',
                'semester' => '1',
                'department' => 'Computer',
                'id_card_photo' => null,
                'application_photos' => [],
                'status' => 'pending',
                'admin_notes' => null,
                'processed_by' => null,
                'processed_at' => null,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'demo_hostel_phone'],
            ['value' => '9800000000', 'type' => 'string', 'description' => 'Demo contact shown in settings pages.']
        );

        AuditLog::updateOrCreate(
            ['user_id' => $student->id, 'action' => 'demo_activity', 'module' => 'Dashboard'],
            [
                'model_type' => User::class,
                'model_id' => $student->id,
                'details' => 'Demo activity log entry for page testing.',
                'ip_address' => '127.0.0.1',
            ]
        );
    }
}