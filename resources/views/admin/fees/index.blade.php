<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Fee Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment submissions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($fees as $fee)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $fee->student->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">Rs {{ number_format($fee->amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $fee->due_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $fee->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($fee->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($fee->type) }}</td>
                            <td class="px-6 py-4">
                                @forelse($fee->payments->sortByDesc('id') as $payment)
                                    <div class="mb-4 rounded border border-gray-200 p-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium">Rs {{ number_format($payment->amount, 2) }}</span>
                                            <span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ ucfirst($payment->status) }}</span>
                                            <span class="text-sm text-gray-500">{{ $payment->date->format('Y-m-d') }} · {{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                                        </div>
                                        <p class="mt-2 text-sm"><strong>Student remark:</strong> {{ $payment->student_remark ?: '—' }}</p>
                                        @if($payment->admin_remark)
                                            <p class="mt-1 text-sm"><strong>Admin remark:</strong> {{ $payment->admin_remark }}</p>
                                        @endif
                                        @if($payment->proof_path)
                                            <a href="{{ route('admin.fees.payments.proof', $payment) }}" target="_blank" rel="noopener" class="mt-2 inline-block text-indigo-600 hover:underline">View proof</a>
                                        @endif

                                        @if($payment->status === 'pending')
                                            <form method="POST" action="{{ route('admin.fees.payments.review', $payment) }}" class="mt-3 space-y-2">
                                                @csrf
                                                <label class="block text-sm font-medium text-gray-700" for="admin_remark_{{ $payment->id }}">Review remark</label>
                                                <textarea id="admin_remark_{{ $payment->id }}" name="admin_remark" rows="2" maxlength="1000" required class="w-full rounded-md border-gray-300 text-sm" placeholder="Explain acceptance or rejection">{{ old('payment_id') == $payment->id ? old('admin_remark') : '' }}</textarea>
                                                @error('admin_remark')
                                                    @if(old('payment_id') == $payment->id)<p class="text-sm text-red-600">{{ $message }}</p>@endif
                                                @enderror
                                                <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                <div class="flex gap-2">
                                                    <button type="submit" name="status" value="accepted" class="rounded bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700">Accept</button>
                                                    <button type="submit" name="status" value="rejected" class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">Reject</button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-sm text-gray-500">No payment submitted</span>
                                @endforelse
                                <a href="{{ route('admin.fees.show', $fee->id) }}" class="text-indigo-600 hover:text-indigo-900">Fee receipt</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $fees->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
