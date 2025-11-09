<x-guest-layout>
    <div class="space-y-8">
        <div class="text-center">
            <h1 class="text-3xl font-semibold text-slate-900">Welcome back</h1>
            <p class="mt-2 text-sm text-slate-600">Choose the sign-in option that applies to your role.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4">
            <a
                href="{{ route('login.authentik') }}"
                class="group inline-flex w-full items-center justify-between rounded-lg bg-indigo-600 px-5 py-4 text-white shadow transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
            >
                <div class="flex flex-col text-left">
                    <span class="text-base font-semibold">Authentik SSO</span>
                    <span class="text-xs text-indigo-100">Administrators &amp; internal staff</span>
                </div>
                <svg class="h-5 w-5 text-indigo-100 transition group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="20" height="20">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>

            <a
                href="{{ route('login.steam') }}"
                class="group inline-flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-5 py-4 text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
            >
                <div class="flex flex-col text-left">
                    <span class="text-base font-semibold">Steam Login</span>
                    <span class="text-xs text-slate-500">Tenant contacts</span>
                </div>
                <svg class="h-5 w-5 text-slate-400 transition group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" width="20" height="20">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>

        <p class="text-center text-xs text-slate-500">
            Need help signing in? Contact the platform administrator.
        </p>
    </div>
</x-guest-layout>
