@extends('layouts.admin')

@section('title', 'Ratings & Reviews Moderation')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Customer Reviews & Rating Moderation</h1>
            <p class="text-xs text-slate-400">Verified customer feedback for provider companies and technicians after job completion.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Request #</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Target Provider</th>
                    <th class="px-6 py-4">Rating</th>
                    <th class="px-6 py-4">Customer Comment</th>
                    <th class="px-6 py-4 text-right">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($reviews as $rev)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4 font-bold text-white">
                            {{ $rev->serviceRequest->request_number }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            {{ $rev->customer->name }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            {{ $rev->provider->name }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-extrabold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                {{ $rev->rating }} ★
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300 italic max-w-xs">
                            "{{ $rev->comment ?? 'No comment text' }}"
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-400">
                            {{ $rev->created_at->inDisplayTimezone()->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No customer reviews submitted yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection
