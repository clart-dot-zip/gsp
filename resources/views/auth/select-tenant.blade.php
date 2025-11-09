@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto py-10">
        <div class="bg-slate-900 border border-slate-700 rounded-xl shadow-lg">
            <div class="border-b border-slate-800 px-6 py-4">
                <h1 class="text-2xl font-semibold text-white">Select a Tenant</h1>
                <p class="mt-1 text-sm text-slate-300">Choose where you want to continue. We'll remember your choice next time.</p>
            </div>

            <div class="px-6 py-5 flex flex-col gap-6">
                @foreach ($tenantOptions as $option)
                    <form method="POST" action="{{ route('tenant-access.update') }}" class="group">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $option['id'] }}">
                        <input type="hidden" name="tenant_contact_id" value="{{ $option['tenant_contact_id'] ?? '' }}">
                        <input type="hidden" name="tenant_player_id" value="{{ $option['tenant_player_id'] ?? '' }}">
                        <input type="hidden" name="origin" value="{{ $origin }}">

                        <button type="submit"
                            class="w-full text-left rounded-lg border px-5 py-4 transition focus:outline-none focus-visible:ring focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900
                                   {{ $option['id'] === $currentTenantId ? 'border-blue-500 bg-blue-500/10 text-white' : 'border-slate-700 bg-slate-800/50 text-slate-200 hover:border-blue-500/70 hover:bg-blue-500/10 hover:text-white' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-medium">{{ $option['name'] }}</span>
                                @if ($option['id'] === $currentTenantId)
                                    <span class="text-xs uppercase tracking-wide text-blue-400">Current</span>
                                @endif
                            </div>

                            @if (! empty($option['roles']))
                                <div class="mt-2 text-sm text-slate-300">
                                    {{ implode(', ', $option['roles']) }}
                                </div>
                            @endif

                            @if (! empty($option['player_note']))
                                <div class="mt-2 text-xs text-amber-300/80">
                                    {{ $option['player_note'] }}
                                </div>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endsection
