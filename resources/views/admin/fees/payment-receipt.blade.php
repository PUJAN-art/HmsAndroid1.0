<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Confirmed Payment Receipt</h2>
            <a href="{{ route('admin.fees.payments.index', ['status' => 'accepted']) }}" class="text-sm font-medium text-indigo-600 hover:underline print:hidden">Back to accepted payments</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm sm:p-10" id="payment-receipt">
                <div class="flex flex-wrap items-center justify-between gap-6 border-b border-gray-200 pb-6">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Manmohan Memorial Polytechnic logo" class="h-20 w-20 rounded-full border border-gray-200 object-cover">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Manmohan Memorial Polytechnic</p>
                            <h1 class="mt-1 text-3xl font-bold text-gray-900">Payment Receipt</h1>
                            <p class="mt-1 text-sm text-gray-500">Official confirmation of fee payment</p>
                        </div>
                    </div>
                    <div class="text-right text-sm text-gray-600">
                        <p><span class="font-medium">Receipt No:</span> PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                        <p class="mt-1"><span class="font-medium">Payment date:</span> {{ $payment->date->format('M d, Y') }}</p>
                        <p class="mt-1"><span class="font-medium">Confirmed:</span> {{ $payment->reviewed_at?->format('M d, Y H:i') ?? $payment->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                <div class="grid gap-6 border-b border-gray-200 py-6 sm:grid-cols-2">
                    <section>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Student</h2>
                        <p class="mt-2 font-semibold text-gray-900">{{ $payment->fee->student->name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $payment->fee->student->email }}</p>
                        <p class="mt-1 text-sm text-gray-600">Student ID: {{ $payment->fee->student->student_id_number ?? $payment->fee->student->admission_no ?? '—' }}</p>
                        <p class="mt-1 text-sm text-gray-600">Room: {{ $payment->fee->student->bed->room->room_number ?? '—' }}</p>
                    </section>
                    <section>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Payment details</h2>
                        <p class="mt-2 text-sm text-gray-700"><span class="font-medium">Method:</span> {{ ucfirst(str_replace('_', ' ', $payment->method)) }}</p>
                        <p class="mt-1 text-sm text-gray-700"><span class="font-medium">Transaction ID:</span> {{ $payment->transaction_id ?: '—' }}</p>
                        <p class="mt-1 text-sm text-gray-700"><span class="font-medium">Status:</span> <span class="font-semibold text-green-700">Confirmed</span></p>
                        <p class="mt-1 text-sm text-gray-700"><span class="font-medium">Confirmed by:</span> {{ $payment->reviewer->name ?? 'Administrator' }}</p>
                    </section>
                </div>

                <div class="grid gap-8 border-b border-gray-200 py-6 lg:grid-cols-[1.4fr_1fr]">
                    <div>
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                                <th class="py-3 pr-4">Description</th>
                                <th class="py-3 pr-4">Due date</th>
                                <th class="py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td class="py-4 pr-4 font-medium text-gray-900">{{ ucfirst($payment->fee->type) }} fee</td>
                                <td class="py-4 pr-4 text-gray-600">{{ \Carbon\Carbon::parse($payment->fee->due_date)->format('M d, Y') }}</td>
                                <td class="py-4 text-right font-semibold text-gray-900">Rs {{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="pt-4 text-right font-semibold text-gray-700">Total received</td>
                                <td class="pt-4 text-right text-lg font-bold text-gray-900">Rs {{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>

                    @if($payment->student_remark || $payment->admin_remark)
                    <div class="space-y-3 rounded-md bg-gray-50 p-4 text-sm">
                        @if($payment->student_remark)
                            <p><span class="font-semibold text-gray-700">Student remark:</span> <span class="text-gray-600">{{ $payment->student_remark }}</span></p>
                        @endif
                        @if($payment->admin_remark)
                            <p><span class="font-semibold text-gray-700">Confirmation remark:</span> <span class="text-gray-600">{{ $payment->admin_remark }}</span></p>
                        @endif
                    </div>
                    @else
                        <div class="flex items-center justify-center rounded-md bg-gray-50 p-4 text-sm text-gray-500">No additional remarks</div>
                    @endif
                </div>

                <div class="flex flex-wrap items-end justify-between gap-8 pt-6">
                    <div>
                        <p class="font-semibold text-green-700">PAYMENT CONFIRMED</p>
                        <p class="mt-1 text-xs text-gray-500">This receipt confirms that the payment above has been reviewed and accepted.</p>
                    </div>
                    <div class="min-w-56 border-t border-gray-400 pt-2 text-center text-xs text-gray-500">
                        Authorized signature
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-center print:hidden">
                <button type="button" onclick="window.print()" class="rounded-md bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-900">Print receipt</button>
            </div>
        </div>
    </div>

    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            aside,
            #scroll-progress-bar,
            header,
            button,
            a {
                display: none !important;
            }

            html,
            body,
            #payment-receipt {
                background: #fff !important;
                color: #111827 !important;
                box-shadow: none !important;
            }

            body {
                font-size: 11pt !important;
            }

            body > div,
            body > div > div,
            main,
            main > div {
                display: block !important;
                width: 100% !important;
                height: auto !important;
                max-width: none !important;
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #payment-receipt {
                width: 100% !important;
                max-width: none !important;
                border: 0 !important;
                padding: 4mm !important;
                margin: 0 !important;
            }

            #payment-receipt img {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            #payment-receipt table,
            #payment-receipt section,
            #payment-receipt dl {
                break-inside: avoid;
            }
        }
    </style>
</x-app-layout>
