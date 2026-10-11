<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Fee Receipt') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200 p-8" id="receipt">
                <div class="border-b border-gray-200 pb-4 mb-4 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">HOSTEL RECEIPT</h1>
                    <div class="text-sm text-gray-500">
                        Date: {{ now()->format('Y-m-d') }}<br>
                        Receipt #: {{ str_pad($fee->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                </div>

                <div class="mb-4">
                    <p class="text-gray-600">Student Name: <span class="font-bold text-gray-800">{{ $fee->student->name }}</span></p>
                    <p class="text-gray-600">Room: <span class="font-bold text-gray-800">{{ $fee->student->bed->room->room_number ?? 'N/A' }}</span></p>
                </div>

                <table class="min-w-full mb-8">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Description</th>
                            <th class="text-right py-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-2">{{ ucfirst($fee->type) }} Fee</td>
                            <td class="text-right py-2">Rs {{ number_format($fee->amount, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-bold">
                            <td class="py-2">Total</td>
                            <td class="text-right py-2">Rs {{ number_format($fee->amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center mt-8">
                    <p class="text-sm text-gray-500">Thank you for your payment.</p>
                </div>
            </div>
            <div class="mt-4 text-center">
                <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded">Print Receipt</button>
            </div>
        </div>
    </div>
</x-app-layout>
