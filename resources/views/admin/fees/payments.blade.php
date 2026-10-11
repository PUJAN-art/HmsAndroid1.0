<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fee Payment Reviews</h2>
                <p class="mt-1 text-sm text-gray-500">Review submitted transfer proof and confirm or reject each payment.</p>
            </div>
            <a href="{{ route('admin.fees.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Fee records</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach([
                    'pending' => ['Awaiting review', 'bg-amber-50 text-amber-800'],
                    'accepted' => ['Accepted', 'bg-green-50 text-green-800'],
                    'rejected' => ['Rejected', 'bg-red-50 text-red-800'],
                    'all' => ['All submissions', 'bg-indigo-50 text-indigo-800'],
                ] as $filter => [$label, $classes])
                    <a href="{{ route('admin.fees.payments.index', ['status' => $filter]) }}" class="rounded-lg border border-gray-200 p-4 transition hover:shadow {{ $classes }}">
                        <div class="text-sm font-medium">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-bold">{{ $filter === 'all' ? $counts->sum() : ($counts[$filter] ?? 0) }}</div>
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">{{ $status === 'all' ? 'All submissions' : ucfirst($status).' submissions' }}</h3>
                    <form method="GET" action="{{ route('admin.fees.payments.index') }}" class="flex items-center gap-2">
                        <label for="payment-status" class="text-sm text-gray-600">Show</label>
                        <select id="payment-status" name="status" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                            @foreach(['pending' => 'Awaiting review', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @forelse($payments as $payment)
                    <article class="border-b border-gray-100 p-5 last:border-b-0">
                        <div class="grid gap-5 lg:grid-cols-[1fr_auto]">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $payment->fee->student->name }}</h4>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $payment->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($payment->status === 'accepted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                    <span class="text-sm text-gray-500">Submission #{{ $payment->id }}</span>
                                </div>

                                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                    <div><dt class="text-gray-500">Fee type</dt><dd class="font-medium text-gray-900">{{ ucfirst($payment->fee->type) }}</dd></div>
                                    <div><dt class="text-gray-500">Payable / submitted</dt><dd class="font-semibold text-gray-900">Rs {{ number_format($payment->fee->amount, 2) }} / Rs {{ number_format($payment->amount, 2) }}</dd></div>
                                    <div><dt class="text-gray-500">Payment date</dt><dd class="font-medium text-gray-900">{{ $payment->date->format('M d, Y') }}</dd></div>
                                    <div><dt class="text-gray-500">Method</dt><dd class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</dd></div>
                                    <div><dt class="text-gray-500">Fee due date</dt><dd class="font-medium text-gray-900">{{ $payment->fee->due_date }}</dd></div>
                                    <div><dt class="text-gray-500">Submitted</dt><dd class="font-medium text-gray-900">{{ $payment->created_at->format('M d, Y H:i') }}</dd></div>
                                </dl>

                                <div class="rounded-md bg-gray-50 p-3 text-sm">
                                    <p><span class="font-semibold text-gray-700">Student remark:</span> <span class="text-gray-600">{{ $payment->student_remark ?: 'No remark provided.' }}</span></p>
                                    @if($payment->admin_remark)
                                        <p class="mt-2"><span class="font-semibold text-gray-700">Review remark:</span> <span class="text-gray-600">{{ $payment->admin_remark }}</span></p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 lg:w-56">
                                @if($payment->proof_path)
                                    <a href="{{ route('admin.fees.payments.proof', $payment) }}" target="_blank" rel="noopener" class="inline-flex justify-center rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Open statement proof</a>
                                @else
                                    <span class="rounded-md bg-red-50 px-4 py-2 text-center text-sm text-red-700">No proof image attached</span>
                                @endif

                                @if($payment->status === 'accepted')
                                    <a href="{{ route('admin.fees.payments.receipt', $payment) }}" class="inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">View receipt</a>
                                @elseif($payment->status === 'pending')
                                    <form method="POST" action="{{ route('admin.fees.payments.review', $payment) }}" class="space-y-2">
                                        @csrf
                                        <label for="review-remark-{{ $payment->id }}" class="block text-sm font-medium text-gray-700">Review remark</label>
                                        <textarea id="review-remark-{{ $payment->id }}" name="admin_remark" rows="3" maxlength="1000" required class="w-full rounded-md border-gray-300 text-sm" placeholder="Required for acceptance or rejection">{{ old('payment_id') == $payment->id ? old('admin_remark') : '' }}</textarea>
                                        @if(old('payment_id') == $payment->id)
                                            @error('admin_remark')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                        @endif
                                        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="submit" name="status" value="accepted" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700">Accept</button>
                                            <button type="submit" name="status" value="rejected" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Reject</button>
                                        </div>
                                    </form>
                                @elseif($payment->reviewer)
                                    <p class="text-xs text-gray-500">Reviewed by {{ $payment->reviewer->name }}{{ $payment->reviewed_at ? ' on '.$payment->reviewed_at->format('M d, Y H:i') : '' }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-16 text-center">
                        <p class="font-medium text-gray-700">No {{ $status === 'all' ? '' : $status }} payment submissions found.</p>
                        <p class="mt-1 text-sm text-gray-500">New student payment proofs will appear here for review.</p>
                    </div>
                @endforelse

                @if($payments->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">{{ $payments->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
