<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->check() ? (auth()->user()->theme === 'dark' ? 'dark' : '') : '' }} {{ auth()->check() ? 'font-' . auth()->user()->font_size : 'font-medium' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hostel Management') }}</title>
    <!-- Home Logo Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z' fill='%231e40af'/></svg">
    <link rel="shortcut icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path d='M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z' fill='%231e40af'/></svg">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        figtree: ['Figtree', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles for Dark Mode and Font Sizes -->
    <style>
        /* Dark Mode Styles */
        .dark {
            color-scheme: dark;
        }
        
        /* Font Size Classes */
        .font-small {
            font-size: 14px;
        }
        .font-medium {
            font-size: 16px;
        }
        .font-large {
            font-size: 18px;
        }
    </style>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    <!-- Theme Initialization Script -->
    <script>
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            @auth
                const theme = '{{ auth()->user()->theme ?? 'light' }}';
                const fontSize = '{{ auth()->user()->font_size ?? 'medium' }}';
                
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                
                document.documentElement.classList.add('font-' + fontSize);
            @endauth
        });
    </script>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900 font-figtree">
    <div id="scroll-progress-bar"></div>
    <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-100 dark:bg-gray-900">
        
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed z-30 inset-y-0 left-0 w-[210px] transition duration-300 transform bg-[#1e3a5f] md:translate-x-0 md:static md:inset-0 flex flex-col overflow-hidden">
            <div class="flex items-center h-14 px-3 gap-2.5">
                <img src="/static/images/logo.jpg" alt="Manmohan Memorial Polytechnic logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;" class="flex-shrink-0">
                <span class="text-white font-bold text-xs leading-tight">Manmohan Memorial<br>Polytechnic</span>
            </div>
            
            <div class="flex-1 overflow-y-auto px-2 py-3 flex flex-col">
                <nav class="space-y-0.5">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#2a4a7f] text-white' : '' }}">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        {{ __('Dashboard') }}
                    </a>
                    
                    @if(Auth::user()->role === 'student')
                        <a href="{{ route('student.rooms.browse') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.rooms.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            {{ __('Browse Rooms') }}
                        </a>
                        <a href="{{ route('student.room-requests') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.room-requests') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            {{ __('My Room Requests') }}
                        </a>
                        <a href="{{ route('student.room') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.room') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            {{ __('My Room') }}
                        </a>
                        <a href="{{ route('student.id-card') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.id-card') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                            {{ __('ID Card') }}
                        </a>
                        <a href="{{ route('student.feedback.create') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.feedback.create') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            {{ __('Feedback') }}
                        </a>
                        <a href="{{ route('student.visit-requests.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.visit-requests.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            {{ __('Visit Requests') }}
                        </a>
                        <a href="{{ route('student.fees') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.fees') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('Fees') }} & {{ __('Payments') }}
                        </a>
                        <a href="{{ route('student.attendance') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.attendance') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            {{ __('Attendance') }}
                        </a>
                        <a href="{{ route('student.activity-logs.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.activity-logs.index') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('Activity Log') }}
                        </a>
                        <a href="{{ route('student.messages.inbox') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.messages.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            {{ __('Messages') }}
                        </a>
                        <a href="{{ route('student.profile') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('student.profile') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            {{ __('Profile') }}
                        </a>
                    @elseif(Auth::user()->role === 'admin')
                        <!-- Admin Navigation Items -->
                        <a href="{{ route('admin.fees.payments.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.fees.payments.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h2m4 0h2m-9 5h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Fee Payments
                            @php($pendingFeePayments = \App\Models\Payment::where('status', 'pending')->count())
                            @if($pendingFeePayments > 0)
                                <span class="ml-auto rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $pendingFeePayments }}</span>
                            @endif
                        </a>
                         <a href="{{ route('admin.rooms.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.rooms.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            {{ __('Rooms') }}
                        </a>
                        <a href="{{ route('admin.room-requests.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.room-requests.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            {{ __('Room Requests') }}
                        </a>
                        <a href="{{ route('admin.users.pending') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.users.pending') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            {{ __('Pending Registrations') }}
                        </a>
                        <a href="{{ route('admin.attendance.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.attendance.index') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            {{ __('Attendance') }}
                        </a>
                        <a href="{{ route('admin.students.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.students.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            {{ __('Students') }}
                        </a>
                        <a href="{{ route('admin.feedback.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.feedback.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            {{ __('Feedback') }}
                        </a>
                        <a href="{{ route('admin.complaints.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.complaints.index') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            {{ __('Complaints') }}
                        </a>
                        <a href="{{ route('admin.notices.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.notices.index') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            {{ __('Notices') }}
                        </a>
                        <a href="{{ route('admin.visitors.requests') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.visitors.requests') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            {{ __('Visit Requests') }}
                        </a>
                        <a href="{{ route('admin.visitors.profiles') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.visitors.profiles') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('Visitor Profiles') }}
                        </a>
                        <a href="{{ route('admin.registration-forms.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.registration-forms.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            {{ __('Published Forms') }}
                        </a>
                        <a href="{{ route('admin.form-submissions.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.form-submissions.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                             <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            {{ __('Student Form Submissions') }}
                        </a>
                        <a href="{{ route('admin.activity-logs.index') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.activity-logs.index') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('Activity Log') }}
                        </a>
                        <a href="{{ route('admin.messages.inbox') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.messages.*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            {{ __('Messages') }}
                        </a>
                        <a href="{{ route('admin.settings') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('admin.settings') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            {{ __('Settings') }}
                        </a>
                    @elseif(Auth::user()->role === 'visitor')
                        <a href="{{ route('visitor.dashboard') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('visitor.dashboard') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                            {{ __('Request Visit') }}
                        </a>
                        <a href="{{ route('visitor.pass') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('visitor.pass*') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h-2m0 0H10m2 0v2m0-2v-2m7-1a9 9 0 11-18 0 9 9 0 0118 0zM9 9h.01M12 9h.01M15 9h.01"></path></svg>
                            {{ __('Visitor Pass') }}
                        </a>
                        <a href="{{ route('visitor.card-profile') }}" class="flex items-center px-3 py-1.5 text-white hover:bg-[#2a4a7f] text-[13px] transition-colors {{ request()->routeIs('visitor.card-profile') ? 'bg-[#2a4a7f] text-white' : '' }}">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h5m-4-6h.01M9 14a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            {{ __('My Card') }}
                        </a>
                    @endif

                </nav>

                <div class="mt-auto pt-3 border-t border-blue-700/40 mx-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-3 py-1.5 text-blue-200 hover:bg-[#2a4a7f] hover:text-white text-[13px] transition-colors">
                            <svg class="h-4 w-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="flex justify-between items-center py-4 px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-400 focus:outline-none md:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <img src="/static/images/logo.jpg" alt="Manmohan Memorial Polytechnic logo" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;" class="ml-4 md:ml-0">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white ml-3">
                        {{ isset($header) ? $header : __('Dashboard') }}
                    </h2>
                </div>

                <div class="flex items-center space-x-3">
                    @auth
                    <div class="flex items-center gap-2 pr-3 border-r border-gray-300 dark:border-gray-600">
                        @if(Auth::user()->profile_photo_path)
                            <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 rounded-full object-cover">
                        @else
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-xs">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="hidden sm:block">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white leading-tight">{{ Auth::user()->name }}</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 capitalize leading-tight">{{ Auth::user()->role }}</p>
                        </div>
                        <svg class="w-3 h-3 text-gray-500 dark:text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    @endauth

                    <!-- Theme Toggle -->
                    <x-theme-toggle />
                    
                    <!-- Language Switcher -->
                    <x-language-switcher />
                    
                    <!-- Notifications -->
                    <x-notification-bell />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
                {{ $slot }}
            </main>
        </div>
        
        @auth
            @include('components.chatbot')
        @endauth
    </div>

    @stack('scripts')
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    confirmButtonColor: '#d33'
                });
            @endif
            
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: `
                        <ul class="text-left text-sm">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    `,
                    confirmButtonColor: '#d33'
                });
            @endif
        });
    </script>
</body>
</html>
