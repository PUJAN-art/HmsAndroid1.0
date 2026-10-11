# Hostel Management System V2 (HMSV2)

## 📋 Project Overview

**Hostel Management System V2 (HMSV2)** is a comprehensive web-based hostel management platform built with Laravel 12 and PHP 8.2. The system manages student accommodation, room assignments, visitor approvals, fees, complaints, feedback, attendance tracking, and notice distribution across a hostel facility.

**Target Users**: Administrators (Wardens/Management), Students (Residents), Visitors (Temporary Guests)

**Primary Goal**: Streamline hostel operations through automated room allocation, dual-tier visitor approval, real-time attendance tracking, and comprehensive financial management.

---

## 🛠️ Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Framework** | Laravel | 12.44.0 |
| **Language** | PHP | 8.2.12 |
| **Database** | MySQL/MariaDB | - |
| **Frontend** | Blade Templates, Tailwind CSS, Alpine.js | Latest |
| **Authentication** | Laravel Breeze (Custom) | - |
| **PDF Generation** | DomPDF | - |
| **Charts & Analytics** | Chart.js | - |
| **Data Security** | bcrypt Hashing, AES-256 Encryption | - |

---

## 👥 User Roles & Access Control

### 1. **Admin (Warden/Management)**
- **Access Level**: Full system permissions
- **Key Responsibilities**:
  - User management (approve/reject students & visitors)
  - Room and bed inventory management
  - Room request approval workflow
  - Fee assignment and financial tracking
  - Complaint resolution
  - ID card management for students
  - Notice board management
  - Visitor request management & tracking
  - Attendance marking and reporting
  - Feedback analytics & insights
  - Audit log monitoring

### 2. **Student (Resident)**
- **Access Level**: Personalized student dashboard
- **Key Responsibilities**:
  - Browse available rooms and request accommodation
  - Approve/reject visitor requests (dual-approval)
  - View assigned room and bed details
  - Track personal fees and payment status
  - Generate and download ID card
  - Submit complaints and track resolutions
  - Submit service feedback & ratings
  - View personal attendance records
  - Receive and view notices

### 3. **Visitor (Temporary Guest)**
- **Access Level**: Limited dashboard, requires approval
- **Restrictions**:
  - Must be approved by host student first
  - Must be approved by admin second
  - Can only access visitor-specific features
  - Cannot access student areas or room management

---

## 📊 Database Schema & Entity Relationships

### Core Entities

#### **1. Users Table**
```
user_id (PK)
├─ name: string
├─ email: string (unique)
├─ password: hashed
├─ role: enum(admin, student, visitor)
├─ phone: string
├─ parent_phone: encrypted
├─ address: encrypted
├─ year: integer (students only)
├─ department: string (students only)
├─ semester: integer (students only)
├─ is_approved: boolean (students)
├─ student_approved: boolean (visitors) [Student's approval status]
├─ admin_approved: boolean (visitors) [Admin's approval status]
├─ rejection_reason: text (for visitor rejection)
├─ profile_photo_path: string (nullable)
├─ bed_id: FK (Beds table)
├─ student_id_number: string (unique, for ID card)
├─ id_card_issued_date: date (nullable)
├─ id_card_expiry_date: date (nullable)
├─ blood_group: encrypted (nullable)
├─ emergency_contact: encrypted (nullable)
├─ emergency_contact_name: encrypted (nullable)
├─ locale: string (user language preference)
├─ theme: string (light/dark mode)
├─ font_size: string (user preference)
├─ is_active: boolean
└─ timestamps: created_at, updated_at
```

**Relationships**:
- `1-to-Many`: User → Fees (student can have multiple fees)
- `1-to-Many`: User → Complaints (student can submit multiple complaints)
- `1-to-Many`: User → RoomRequests (student can request multiple rooms)
- `1-to-Many`: User → Feedback (student can submit multiple feedback)
- `1-to-Many`: User → Attendance (student has multiple attendance records)
- `1-to-Many`: User → Visitors (student can host multiple visitors)
- `1-to-One`: User → Bed (student assigned to one bed)

---

#### **2. Rooms Table**
```
room_id (PK)
├─ room_number: string (unique)
├─ type: enum(Standard, Deluxe, VIP)
├─ capacity: integer (max occupants)
├─ floor: integer
├─ block: string (building section)
├─ rent: decimal (monthly rent)
├─ status: enum(available, occupied, maintenance)
├─ description: text
├─ photo_path: string (nullable)
├─ amenities: JSON array (WiFi, AC, Attached Bathroom, etc.)
└─ timestamps: created_at, updated_at
```

**Relationships**:
- `1-to-Many`: Room → Beds (one room has multiple beds)
- `1-to-Many`: Room → RoomRequests (room can receive multiple requests)

**Key Methods**:
- `getAvailableBedsCount()`: Returns count of unoccupied beds
- `isFullyOccupied()`: Boolean check if all beds are taken

---

#### **3. Beds Table**
```
bed_id (PK)
├─ room_id: FK (Rooms table)
├─ bed_number: integer
├─ is_occupied: boolean (current occupancy status)
└─ timestamps: created_at, updated_at
```

**Relationships**:
- `1-to-One`: Bed → User (one bed assigned to one student)
- `Many-to-One`: Bed → Room (multiple beds per room)

**Purpose**: Granular tracking of individual bed availability within each room

---

#### **4. RoomRequests Table**
```
request_id (PK)
├─ user_id: FK (Users table - requesting student)
├─ room_id: FK (Rooms table - requested room)
├─ bed_id: FK (Beds table, nullable)
├─ status: enum(pending, approved, rejected)
├─ rejection_reason: text (nullable)
├─ requested_at: timestamp
├─ approved_at: timestamp (nullable)
├─ approved_by: FK (Users table - admin who approved)
└─ timestamps: created_at, updated_at
```

**Workflow**:
1. Student requests room → status = 'pending'
2. Admin reviews → Approves request
3. System finds first available bed → Updates user.bed_id & bed.is_occupied = true
4. Status updates to 'approved'

---

#### **5. Fees Table**
```
fee_id (PK)
├─ user_id: FK (Users table)
├─ amount: decimal (fee amount)
├─ due_date: date
├─ status: enum(pending, paid, overdue)
├─ type: enum(rent, maintenance, deposit, utilities)
├─ description: text
├─ payment_date: date (nullable)
└─ timestamps: created_at, updated_at
```

**Relationships**:
- `Many-to-One`: Fees → User
- `1-to-Many`: Fee → Payments (payment tracking & history)

---

#### **6. Complaints Table**
```
complaint_id (PK)
├─ user_id: FK (Users table - complainant)
├─ category: enum(maintenance, cleanliness, noise, facilities, other)
├─ subject: string (brief title)
├─ description: text (detailed complaint)
├─ status: enum(open, in-progress, resolved)
├─ priority: enum(low, medium, high)
├─ response: text (admin's reply, nullable)
├─ resolved_at: timestamp (nullable)
└─ timestamps: created_at, updated_at
```

---

#### **7. Feedback Table**
```
feedback_id (PK)
├─ user_id: FK (Users table - feedback provider)
├─ cleanliness_rating: integer (1-5 stars)
├─ food_rating: integer (1-5 stars)
├─ staff_rating: integer (1-5 stars)
├─ facilities_rating: integer (1-5 stars)
├─ safety_rating: integer (1-5 stars)
├─ overall_rating: float (calculated average)
├─ comments: text (optional detailed feedback)
├─ status: enum(pending, reviewed, addressed)
└─ timestamps: created_at, updated_at
```

---

#### **8. Attendance Table**
```
attendance_id (PK)
├─ user_id: FK (Users table - student)
├─ date: date
├─ status: enum(present, absent, on-leave)
├─ marked_by: FK (Users table - admin)
└─ timestamps: created_at, updated_at
```

---

#### **9. Notices Table**
```
notice_id (PK)
├─ title: string
├─ content: longtext
├─ audience: enum(all, students, visitors)
├─ priority: enum(normal, important, urgent)
├─ created_by: FK (Users table - admin author)
├─ published_at: timestamp
└─ timestamps: created_at, updated_at
```

---

#### **10. Visitors Table**
```
visitor_id (PK)
├─ name: string
├─ email: string
├─ phone: string
├─ student_id: FK (Users table - host student)
├─ purpose: string
├─ visit_date: date
├─ student_approved: boolean
├─ admin_approved: boolean
├─ rejection_reason: text (nullable)
├─ status: enum(pending, approved, rejected, completed)
└─ timestamps: created_at, updated_at
```

---

#### **11. AuditLog Table** (Security & Compliance)
```
log_id (PK)
├─ user_id: FK (Users table - who performed action)
├─ action: string (e.g., "room_assigned", "fee_created")
├─ description: text (detailed action description)
├─ ip_address: string
├─ user_agent: string
└─ timestamps: created_at, updated_at
```

**Tracked Actions**:
- User logins/logouts
- Admin approvals/rejections
- Room assignments/unassignments
- Complaint submissions/resolutions
- Fee creations/modifications

---

#### **12. Notifications Table** (Real-time Alerts)
```
notification_id (PK)
├─ user_id: FK (Users table)
├─ type: string (e.g., "room_approved", "fee_due")
├─ message: text
├─ is_read: boolean (default: false)
├─ data: JSON (contextual data)
└─ timestamps: created_at, updated_at
```

---

#### **13. Additional Tables**
- **Payments**: Track fee payments with bank/method details
- **Leaves**: Student leave requests with date ranges
- **Messages**: Direct messaging between users
- **Settings**: System-wide configuration
- **RegistrationForm**: Form submission data for public registrations
- **FormSubmission**: User form submission tracking
- **PasswordOtp**: OTP management for password reset

---

## 🔄 Data Flow Diagrams (DFD)

### **DFD Level 0 (System Context Diagram)**
```
External Actor: Hostel Admin/Warden
    ↓ (Manages Users, Rooms, Finances)
    ↓
[Hostel Management System]
    ↓ (Room Info, Fees, Notices)
    ↓
External Actor: Students
    ↓ (Browse Rooms, Request Accommodation)

External Actor: Visitors
    ↓ (Request Approval)
```

### **DFD Level 1 - Main Processes**

#### **Process 1: User Authentication & Authorization**
```
User Input (Email, Password)
    ↓
[Authentication Service]
    ├─ Validate Email/Password
    ├─ Check Role (admin/student/visitor)
    └─ Verify Approval Status (for students & visitors)
    ↓
Session Created → User Dashboard
```

#### **Process 2: Visitor Approval Workflow (Dual-Tier)**
```
Visitor Registration Form
    ↓
[Database] → Store: student_approved=0, admin_approved=0
    ↓
Student Receives Notification
    ↓
[Student Approval Check]
    ├─ If APPROVE: Update student_approved=1
    └─ If REJECT: Store rejection_reason, Update student_approved=0
    ↓
Admin Receives Notification
    ↓
[Admin Approval Check]
    ├─ If APPROVE: Update admin_approved=1, Status=ACTIVE
    └─ If REJECT: Update admin_approved=0, Status=INACTIVE
    ↓
Visitor Can Login ONLY IF: student_approved=1 AND admin_approved=1
```

#### **Process 3: Room Booking & Auto Bed Assignment**
```
Student Browses Available Rooms
    ↓
[Filter & Select Room]
    ├─ Filter by Type (Standard/Deluxe/VIP)
    └─ Check Available Beds
    ↓
Student Submits Room Request
    ↓
[Database] → Store RoomRequest with status='pending'
    ↓
Admin Reviews Request
    ↓
[Admin Approves]
    ├─ Query: Find first bed where is_occupied=0
    ├─ Update: user.bed_id = <found bed>
    ├─ Update: bed.is_occupied = 1
    ├─ Update: room_request.status = 'approved'
    └─ Update: room.status based on occupancy
    ↓
Student Assigned to Room
    ↓
[Notification] → Sent to Student
```

#### **Process 4: Fee Management**
```
Admin Creates Fee for Student
    ↓
[Fee Service]
    ├─ Fee Type: Rent/Maintenance/Deposit
    ├─ Set Due Date
    ├─ Set Amount
    └─ Status = 'pending'
    ↓
[Database] → Store in Fees table
    ↓
Student Views Fees Dashboard
    ↓
[Payment Processing]
    ├─ Online Payment
    └─ Update Status = 'paid'
    ↓
Admin Generates Fee Report
```

#### **Process 5: Complaint Management**
```
Student Submits Complaint
    ↓
[Complaint Service]
    ├─ Validate: Subject, Description, Category, Priority
    ├─ Status = 'open'
    └─ Assign to Admin
    ↓
[Database] → Store in Complaints table
    ↓
Admin Reviews Complaint
    ↓
[Admin Updates]
    ├─ Status = 'in-progress'
    ├─ Add Response
    └─ Status = 'resolved'
    ↓
Student Receives Notification
```

#### **Process 6: Feedback & Analytics**
```
Student Submits Feedback
    ↓
[Rating Service]
    ├─ Cleanliness (1-5): ★★★★☆
    ├─ Food (1-5): ★★★☆☆
    ├─ Staff (1-5): ★★★★★
    ├─ Facilities (1-5): ★★★★☆
    └─ Safety (1-5): ★★★★★
    ↓
[Calculate Average] → overall_rating = avg(all ratings)
    ↓
[Database] → Store in Feedback table
    ↓
Admin Accesses Analytics Dashboard
    ↓
[Chart.js] → Visualize:
    ├─ Bar charts (ratings per category)
    ├─ Pie charts (satisfaction distribution)
    └─ Trend analysis (monthly comparisons)
```

#### **Process 7: Attendance Management**
```
Admin Marks Attendance
    ↓
[Attendance Service]
    ├─ Date Selection
    ├─ Student Status (Present/Absent/Leave)
    └─ Store IP & Timestamp
    ↓
[Database] → Store in Attendance table
    ↓
Student Views Attendance Records
    ↓
[Export Function]
    └─ Generate Excel/PDF Report
```

---

## 🏗️ System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    USER INTERFACE LAYER                      │
│  (Blade Templates, Tailwind CSS, Alpine.js)                 │
│  ┌──────────────┬──────────────┬────────────┐               │
│  │ Admin Panel  │ Student Portal │ Visitor Portal        │
│  └──────────────┴──────────────┴────────────┘               │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│               ROUTING & MIDDLEWARE LAYER                     │
│  (Routes, Authentication, Authorization, Role-Check)        │
│  ├─ Route Groups: /admin, /student, /visitor               │
│  ├─ Middleware: auth, verified, role:admin, role:student   │
│  └─ CAPTCHA, CSRF Protection                               │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│            CONTROLLER & BUSINESS LOGIC LAYER                 │
│  Request Processing, Validation, Data Transformation        │
│  ┌──────────────┬──────────────┬────────────┬────────┐      │
│  │ AdminControl │ StudentControl│ RoomControl│ FeeControl   │
│  │ VisitorControl│ ComplaintControl│ FeedbackController │
│  │ AttendanceController│ IDCardController  │              │
│  └──────────────┴──────────────┴────────────┴────────┘      │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│             SERVICE LAYER (Business Logic)                   │
│  ├─ AuditLogger Service (Compliance Tracking)               │
│  ├─ NotificationService (Real-time Alerts)                  │
│  ├─ PaymentService (Fee Processing)                         │
│  ├─ MailService (Email Notifications, OTP)                  │
│  └─ ValidationServices (Input Sanitization)                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│             MODEL & ORM LAYER (Eloquent)                     │
│  Data Representation & Relationships                         │
│  ┌──────────────┬──────────────┬────────────┬────────┐      │
│  │ User Model   │ Room Model   │ Fee Model  │ Complaint│
│  │ Feedback     │ Attendance   │ Visitor    │ Notice │
│  │ RoomRequest  │ Bed Model    │ Payment    │ AuditLog    │
│  └──────────────┴──────────────┴────────────┴────────┘      │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│              DATABASE LAYER (MySQL/MariaDB)                  │
│  Persistent Data Storage with Encryption & Indexing         │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Tables: users, rooms, beds, room_requests, fees,      │ │
│  │ complaints, feedback, attendance, notices, visitors,   │ │
│  │ payments, leaves, messages, audit_logs, notifications │ │
│  └────────────────────────────────────────────────────────┘ │
└──────────────────────┬──────────────────────────────────────┘
                       │
                ┌──────┴──────┐
                │             │
         ┌──────▼──────┐ ┌─────▼──────┐
         │Storage Layer │ │Cache Layer │
         │ (Files/Images)│ │ (Redis)   │
         └──────────────┘ └───────────┘
```

---

## 🛣️ API Routes & Endpoints

### **Authentication Routes**
- `POST /register` - User registration
- `POST /login` - User login with CAPTCHA
- `POST /logout` - Logout (all roles)

### **Admin Routes** `(/admin/*)`
- **User Management**:
  - `GET /admin/users/pending` - View pending user approvals
  - `POST /admin/users/{id}/approve` - Approve user
  - `DELETE /admin/users/{id}/reject` - Reject user

- **Room Management** (Resource):
  - `GET /admin/rooms` → List all rooms
  - `POST /admin/rooms` → Create new room
  - `PUT /admin/rooms/{id}` → Update room details
  - `DELETE /admin/rooms/{id}` → Delete room

- **Room Requests**:
  - `GET /admin/room-requests` → View all pending requests
  - `POST /admin/room-requests/{id}/approve` → Auto-assign bed & approve
  - `POST /admin/room-requests/{id}/reject` → Reject request

- **Fee Management** (Resource):
  - `GET /admin/fees` → List all fees
  - `POST /admin/fees` → Create new fee
  - `PUT /admin/fees/{id}` → Update fee
  - `DELETE /admin/fees/{id}` → Delete fee

- **Complaints**:
  - `GET /admin/complaints` → View all complaints
  - `PUT /admin/complaints/{id}` → Update complaint status/response

- **Feedback Analytics**:
  - `GET /admin/feedback` → View all feedback
  - `GET /admin/feedback/analytics` → View charts & statistics

- **Attendance**:
  - `GET /admin/attendance` → Mark attendance
  - `POST /admin/attendance/store` → Submit attendance
  - `GET /admin/attendance/export` → Export as Excel/PDF

- **Visitor Management**:
  - `GET /admin/visitors` → View all visitor requests
  - `PUT /admin/visitors/{id}` → Edit/update visitor details

- **Notices** (Resource):
  - Full CRUD with audience targeting

### **Student Routes** `(/student/*)`
- **Dashboard**:
  - `GET /student/dashboard` → Student home
  - `GET /student/profile` → View profile

- **Room Management**:
  - `GET /student/rooms/browse` → Browse available rooms (filtered)
  - `POST /student/rooms/{id}/request` → Request room
  - `GET /student/room-requests` → View own requests
  - `GET /student/room` → View assigned room

- **Visitor Approval**:
  - `GET /student/visitors/pending` → View pending visitor requests
  - `POST /student/visitors/{id}/approve` → Approve visitor (with mandatory reason requirement)
  - `POST /student/visitors/{id}/reject` → Reject visitor

- **ID Card**:
  - `GET /student/id-card` → View ID card
  - `GET /student/id-card/download` → Download as PDF

- **Fees**:
  - `GET /student/fees` → View own fees

- **Complaints**:
  - `POST /student/complaints` → Submit complaint
  - `GET /student/complaints` → View own complaints

- **Feedback**:
  - `POST /feedback` → Submit feedback

### **Visitor Routes** `(/visitor/*)`
- `GET /visitor/dashboard` → Visitor home
- `POST /visitor/request` → Request visit approval

### **Shared Routes** (All Authenticated)
- `POST /chatbot/ask` → AI chatbot interaction
- `POST /theme/toggle` → Switch light/dark mode
- `GET /locale/{locale}` → Change language
- `GET /notifications` → View notifications
- `POST /notifications/{id}/read` → Mark notification as read

---

## 🔐 Security Features Implementation

### **Data Security**
- ✅ **HTTPS Enforcement**: All connections encrypted in production
- ✅ **Password Hashing**: bcrypt algorithm (never stored in plain text)
- ✅ **Database Encryption**: Sensitive fields encrypted with AES-256
  - `address`, `parent_phone`, `emergency_contact`, `emergency_contact_name`, `blood_group`
- ✅ **CSRF Protection**: Token validation on all forms
- ✅ **SQL Injection Prevention**: Eloquent ORM parameterized queries
- ✅ **XSS Protection**: Blade template escaping

### **Application-Level Security**
- ✅ **CAPTCHA**: Math-based bot protection on login & registration
- ✅ **Input Validation**: Strict validation + file type checking
- ✅ **Rate Limiting**: Prevent brute force attacks
- ✅ **Secure File Upload**: Image size & type restrictions (JPG/PNG, <1MB)

### **Access Control**
- ✅ **Role-Based Access Control (RBAC)**: Middleware enforces permissions
- ✅ **Authentication Middleware**: Redirects unauthenticated users
- ✅ **Authorization Middleware**: Prevents privilege escalation
- ✅ **Data Filtering**: Students see only their own data

### **Compliance & Audit**
- ✅ **Audit Logging**: All critical actions tracked with IP & timestamp
- ✅ **Two-Tier Approval**: Visitor approval requires both student & admin consent
- ✅ **Activity Monitoring**: Real-time audit trail for regulatory compliance

---

## 🔄 Key System Workflows

### **Workflow 1: Visitor Approval (Two-Tier System)**
```
Visitor Registration
    ↓
Email Verification
    ↓
Student Review (must approve or reject with reason)
    ├─ REJECT → Visitor cannot login (rejection recorded)
    └─ APPROVE → Notification sent to Admin
    ↓
Admin Final Review
    ├─ REJECT → Visitor rejected
    └─ APPROVE → Visitor can login
    ↓
Visitor Gets Full Access
```

### **Workflow 2: Room Request & Auto-Assignment**
```
Student Browses Rooms
    ↓
Selects Room + Submits Request
    ↓
System Creates RoomRequest (status: pending)
    ↓
Admin Reviews Request
    ↓
Admin Approval Triggers:
    ├─ Find first unoccupied bed (is_occupied=0)
    ├─ Update user.bed_id = <bed>
    ├─ Set bed.is_occupied = 1
    ├─ Update room status
    └─ Send Notification to Student
    ↓
Student Assigned to Room & Bed
```

### **Workflow 3: ID Card Generation & Management**
```
Admin Sets Student ID Details
    ├─ Student ID Number
    ├─ Blood Group
    ├─ Issue Date
    └─ Expiry Date
    ↓
Student Accesses ID Card
    ↓
Download as PDF (uses DomPDF)
    ↓
Printable Official Document
```

### **Workflow 4: Feedback Collection & Analytics**
```
Student Submits Feedback
    ├─ Cleanliness: ★★★★☆
    ├─ Food: ★★★☆☆
    ├─ Staff: ★★★★★
    ├─ Facilities: ★★★★☆
    └─ Safety: ★★★★★
    ↓
System Calculates Average Ratings
    ↓
Admin Accesses Analytics Dashboard
    ↓
Chart.js Visualizes:
    ├─ Category averages (bar chart)
    ├─ Satisfaction distribution (pie chart)
    ├─ Trend analysis (line chart)
    └─ Improvement suggestions
```

---

## 📁 Project File Structure

```
hostel-management-systemV2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php
│   │   │   ├── StudentController.php
│   │   │   ├── VisitorController.php
│   │   │   ├── RoomController.php
│   │   │   ├── FeeController.php
│   │   │   ├── ComplaintController.php
│   │   │   ├── FeedbackController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── IDCardController.php
│   │   │   ├── Auth/
│   │   │   └── Admin/
│   │   ├── Middleware/
│   │   │   └── Role-based middlewares
│   │   └── Requests/ (Form Validation Classes)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Room.php
│   │   ├── Bed.php
│   │   ├── RoomRequest.php
│   │   ├── Fee.php
│   │   ├── Complaint.php
│   │   ├── Feedback.php
│   │   ├── Attendance.php
│   │   ├── Notice.php
│   │   ├── Visitor.php
│   │   ├── Payment.php
│   │   ├── AuditLog.php
│   │   ├── Notification.php
│   │   └── Others...
│   ├── Services/
│   │   ├── AuditLogger.php
│   │   └── NotificationService.php
│   ├── Mail/
│   │   └── SendOtpMail.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── migrations/ (Schema definitions)
│   ├── seeders/ (Sample data)
│   └── factories/
├── routes/
│   ├── web.php (All routes defined here)
│   └── auth.php
├── resources/
│   ├── views/
│   │   ├── admin/ (Admin dashboard views)
│   │   ├── student/ (Student views)
│   │   ├── visitor/ (Visitor views)
│   │   └── layouts/ (Shared layouts)
│   ├── css/
│   └── js/
├── storage/
│   ├── app/public/ (User uploads)
│   └── logs/ (Error logs)
├── public/
│   ├── storage/ (Symlink to storage/app/public)
│   └── build/ (Compiled assets)
├── config/
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   └── mail.php
├── composer.json (PHP dependencies)
├── artisan (Laravel CLI)
└── .env (Environment variables)
```

---

## 🚀 Installation & Setup

### **Prerequisites**
- PHP 8.2+ 
- MySQL/MariaDB
- Composer
- Node.js (for Vite asset bundling)

### **Setup Steps**
```bash
# Clone repository
git clone <repo-url>
cd hostel-management-systemV2

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Generate app key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=hostel_management
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed demo data for local/testing only
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

Website accessible at: `http://localhost:8000`

### Local Demo Accounts

`DatabaseSeeder` skips demo data outside the `local` and `testing` environments. In local development, the seeder creates these accounts (password for each: `password`):

| Role | Email | Sample data |
|------|-------|-------------|
| Admin | `admin@hostel.com` | Dashboard, approvals, rooms, fees, notices, and logs |
| Student | `john@hostel.com` | Assigned room, fees/receipt, attendance, complaints, feedback, visitor, messages, and notices |
| Pending student | `pending@hostel.com` | Pending registration and approval flow |
| Student with pending room request | `requests@hostel.com` | Room request history and pending state |
| Unassigned student | `unassigned@hostel.com` | Submit a new room request and test the no-room state |
| Visitor | `visitor@hostel.com` | Approved visitor account for visitor-role checks |

Run `php artisan db:seed` again to restore/update demo records. Demo credentials are for local testing only; never use them in production.

---

## 📊 Use Cases & Scenarios

### **Use Case 1: Student Room Booking**
1. Student logs in
2. Navigates to "Browse Rooms"
3. Filters rooms by type (Deluxe, Standard)
4. Views room details with photos & amenities
5. Submits room request
6. Admin reviews and approves
7. System automatically assigns an available bed
8. Student receives confirmation notification

### **Use Case 2: Visitor Approval**
1. Visitor registers with student as "host"
2. Student receives notification
3. Student reviews visitor info and approves/rejects
4. If approved, notification sent to Admin
5. Admin reviews and approves/rejects
6. Visitor can only login if both approvals received
7. Admin can track visitor details in "Visit Requests"

### **Use Case 3: Fee Management**
1. Admin assigns fee to student
2. Fee appears on student dashboard
3. Student can view fee details (due date, amount, status)
4. Student makes payment
5. Admin marks as paid
6. Status updates from "pending" to "paid"
7. Dashboard updated with payment history

### **Use Case 4: Complaint Resolution**
1. Student submits complaint (e.g., water issue)
2. System categorizes and assigns priority
3. Admin notified
4. Admin updates status to "in-progress"
5. Admin adds response/resolution details
6. Status updated to "resolved"
7. Student receives notification of resolution

---

## 📈 Analytics & Reporting

### **Admin Dashboard Includes**:
- 📊 User Statistics (Students, Visitors, Pending Approvals)
- 💰 Financial Summary (Total Fees, Pending Payments, Collections)
- 🛏️ Room Occupancy Rates (Occupied vs Available Beds)
- ⭐ Feedback Analytics (Average ratings per category with charts)
- 📋 Complaint Status Distribution
- 📅 Attendance Summary
- 🔔 Recent Activities & Notifications

### **Student Dashboard Includes**:
- 🏠 Assigned Room & Bed Details
- 💳 Fee Status & Payment History
- 🎫 ID Card Information
- 👥 Visitor Approval Requests
- 📝 Complaint History
- 📊 Personal Attendance Records
- 📢 System Notices & Announcements

---

## 🌐 Localization & Preferences

- **Language Support**: English, Nepali (Extensible)
- **Theme**: Light Mode / Dark Mode
- **Font Size**: User-selectable text size
- **Preferences Stored**: In `users` table for persistence across sessions

---

## ⚙️ Maintenance & Support

### **Logging**
- All errors logged in `storage/logs/`
- Critical actions recorded in AuditLog table
- Date-based log rotation

### **Clear Cache**
```bash
php artisan optimize:clear
php artisan cache:clear
```

### **Database Backup**
```bash
mysqldump -u root -p hostel_management > backup.sql
```

---

## 📝 Notes for ChatGPT Diagram Generation

**For ER Diagram Generation, Include**:
- All 13+ entities listed above
- Primary keys (PK) and foreign keys (FK)
- Relationship types (1-to-1, 1-to-Many, Many-to-Many)
- Key attributes for each entity
- Cardinality notation

**For DFD Generation, Consider**:
- 7 main processes: Authentication, Visitor Approval, Room Booking, Fee Management, Complaints, Feedback, Attendance
- External entities: Admin, Student, Visitor
- Data stores: Database, File Storage
- Data flows with clear labels

**For Presentation, Highlight**:
- System overview & objectives
- User roles and access levels
- Key features (auto bed assignment, dual-approval, analytics)
- Security mechanisms
- Technology stack
- System workflows with visual flowcharts

---

## 📞 Support & Questions

For technical documentation, see:
- `project_details/00_PROJECT_OVERVIEW.txt`
- `project_details/02_MODELS.txt`
- `project_details/03_ROUTES.txt`
- `project_details/06_SYSTEM_WORKFLOWS.txt`
- `SECURITY_FEATURES.txt`

---

**Last Updated**: April 2026  
**System Version**: 2.0.0  
**Framework**: Laravel 12.44.0  
**PHP Version**: 8.2.12
