<x-guest-layout>
    <div class="space-y-10">
        <div class="space-y-3 text-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 9.586V6z" clip-rule="evenodd" />
                </svg>
                Secure Console Access
            </span>
            <h1 class="text-3xl font-semibold text-slate-900">Choose how you sign in</h1>
            <p class="text-sm text-slate-600">Authentik is reserved for core staff. Tenant contacts should continue with Steam.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <a
                href="{{ route('login.authentik') }}"
                class="group flex flex-col justify-between rounded-2xl border border-indigo-100 bg-indigo-600 p-6 text-white shadow-lg transition hover:-translate-y-1 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300"
            >
                <div class="space-y-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" class="text-white" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5l-9 9M9 7.5h7.5V15" />
                        </svg>
                    </span>
                    <div class="space-y-1">
                        <h2 class="text-xl font-semibold">Authentik SSO</h2>
                        <p class="text-sm text-indigo-100">For administrators and internal operators who manage tenants and system-wide settings.</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between text-sm text-indigo-100">
                    <span>Continue with Authentik</span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" class="transition group-hover:translate-x-1" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>

            <a
                href="{{ route('login.steam') }}"
                class="group flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-lg transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-slate-300"
            >
                <div class="space-y-4">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" class="text-slate-600" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5.25h13.5M3 9.75h18M3 14.25h13.5M3 18.75h18" />
                        </svg>
                    </span>
                    <div class="space-y-1">
                        <h2 class="text-xl font-semibold">Steam Login</h2>
                        <p class="text-sm text-slate-600">For tenant contacts who access resources and updates scoped to their organisation.</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between text-sm text-slate-500">
                    <span>Continue with Steam</span>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" class="text-slate-400 transition group-hover:translate-x-1" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white/60 p-4 text-xs text-slate-500">
            <p><strong>Heads up:</strong> Steam authentication relies on OpenID. Ensure this site is accessible over HTTPS with the same domain you use in your Steam application settings.</p>
        </div>
    </div>
</x-guest-layout>
