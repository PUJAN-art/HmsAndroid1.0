<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Fees & Payments') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ showPayment: {{ $errors->any() ? 'true' : 'false' }}, feeId: @js(old('fee_id', '')), amount: @js(old('amount', '')) }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
                    @endif

                    @if($fees->isEmpty())
                        <div class="text-center py-10 text-gray-500">No fee records found.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($fees as $fee)
                                        @php($latestPayment = $fee->payments->sortByDesc('id')->first())
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($fee->type) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold">Rs {{ number_format($fee->amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($fee->due_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($fee->status === 'paid')
                                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Paid</span>
                                                @elseif($latestPayment?->status === 'pending')
                                                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">Awaiting review</span>
                                                @elseif($latestPayment?->status === 'rejected')
                                                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Payment rejected</span>
                                                    @if($latestPayment->admin_remark)
                                                        <p class="mt-1 max-w-xs whitespace-normal text-xs text-gray-600">Admin: {{ $latestPayment->admin_remark }}</p>
                                                    @endif
                                                @else
                                                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">{{ ucfirst($fee->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($fee->status !== 'paid' && $latestPayment?->status !== 'pending')
                                                    <button
                                                        type="button"
                                                        @click="feeId = '{{ $fee->id }}'; amount = '{{ number_format((float) $fee->amount, 2, '.', '') }}'; showPayment = true"
                                                        class="rounded bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                                                        {{ $latestPayment?->status === 'rejected' ? 'Resubmit proof' : 'Pay Now' }}
                                                    </button>
                                                @else
                                                    <span class="{{ $fee->status === 'paid' ? 'font-bold text-green-600' : 'text-gray-500' }}">
                                                        {{ $fee->status === 'paid' ? 'Paid' : 'Under review' }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div x-show="showPayment" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
                            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                                <button type="button" class="fixed inset-0 bg-gray-900/60" aria-label="Close payment form" @click="showPayment = false"></button>
                                <div class="relative z-10 w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                                    <h3 id="payment-modal-title" class="text-lg font-semibold text-gray-900">Submit payment proof</h3>
                                    <p class="mt-1 text-sm text-gray-600">Enter your transfer details. The payment is complete only after admin approval.</p>

                                    <form method="POST" action="{{ route('student.fees.payments.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                                        @csrf
                                        <input type="hidden" name="fee_id" :value="feeId">

                                        <div>
                                            <label for="payment-amount" class="block text-sm font-medium text-gray-700">Payable amount</label>
                                            <div class="mt-1 flex items-center rounded-md border border-gray-300 px-3">
                                                <span class="text-sm text-gray-500">Rs</span>
                                                <input id="payment-amount" name="amount" type="number" step="0.01" min="0.01" readonly required x-model="amount" class="w-full border-0 focus:ring-0">
                                            </div>
                                            @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                            @error('fee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="payment-method" class="block text-sm font-medium text-gray-700">Payment method</label>
                                            <select id="payment-method" name="method" required class="mt-1 w-full rounded-md border-gray-300">
                                                <option value="">Select payment method</option>
                                                <option value="esewa" @selected(old('method') === 'esewa')>eSewa</option>
                                                <option value="khalti" @selected(old('method') === 'khalti')>Khalti</option>
                                                <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>Bank transfer</option>
                                                <option value="other" @selected(old('method') === 'other')>Other</option>
                                            </select>
                                            @error('method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="payment-proof" class="block text-sm font-medium text-gray-700">Statement / payment proof image</label>
                                            <input id="payment-proof" name="proof" type="file" accept="image/jpeg,image/png,image/webp" required class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-sm">
                                            <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP; maximum 5 MB.</p>
                                            @error('proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="payment-remark" class="block text-sm font-medium text-gray-700">Remark</label>
                                            <textarea id="payment-remark" name="remark" rows="3" maxlength="1000" required class="mt-1 w-full rounded-md border-gray-300" placeholder="Add a note about this payment">{{ old('remark') }}</textarea>
                                            @error('remark')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="flex justify-end gap-3 border-t pt-4">
                                            <button type="button" @click="showPayment = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</button>
                                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Submit for review</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
