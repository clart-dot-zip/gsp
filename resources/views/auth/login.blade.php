<x-guest-layout>
    <div class="grid gap-10 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="flex flex-col justify-between gap-10 rounded-3xl border border-white/10 bg-white/5 p-10 md:p-12">
            <div class="space-y-6">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-wider text-indigo-100">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" class="text-indigo-200">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Gateway Services Platform
                </span>
                <div class="space-y-4">
                    <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">Choose the way you sign in</h1>
                    <p class="max-w-xl text-base text-slate-200/80 sm:text-lg">
                        Core operators use Authentik SSO for full administrative access. Tenant contacts and community players can join through Steam to collaborate on support tickets.
                    </p>
                </div>
            </div>

            <dl class="grid gap-6 text-sm text-slate-200/80 sm:grid-cols-2">
                <div class="space-y-2 rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                    <dt class="text-xs font-semibold uppercase tracking-widest text-slate-300/80">Authentik</dt>
                    <dd class="text-sm text-slate-200/80">Staff SSO with elevated permissions across tenants and infrastructure tooling.</dd>
                </div>
                <div class="space-y-2 rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                    <dt class="text-xs font-semibold uppercase tracking-widest text-slate-300/80">Steam Contacts</dt>
                    <dd class="text-sm text-slate-200/80">Verified tenant contacts can review operational dashboards and manage support work.</dd>
                </div>
                <div class="space-y-2 rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                    <dt class="text-xs font-semibold uppercase tracking-widest text-slate-300/80">Steam Players</dt>
                    <dd class="text-sm text-slate-200/80">Regular players can log in to follow and contribute to tickets for the tenants they play on.</dd>
                </div>
            </dl>
        </section>

        <section class="flex flex-col gap-8 rounded-3xl border border-white/10 bg-slate-950/70 p-10 md:p-12">
            <header class="space-y-3">
                <h2 class="text-2xl font-semibold text-white">Sign in to continue</h2>
                <p class="text-sm text-slate-300/80">Select the identity provider that matches your role.</p>
            </header>

            @if (session('status'))
                <div class="rounded-2xl border border-emerald-400/40 bg-emerald-500/10 p-4 text-sm text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col gap-4">
                <a
                    href="{{ route('login.authentik') }}"
                    class="group flex items-center justify-between gap-4 rounded-2xl border border-indigo-400/40 bg-indigo-500/90 px-6 py-5 text-base font-semibold text-white shadow-lg shadow-indigo-900/40 transition hover:-translate-y-1 hover:bg-indigo-500"
                >
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/15">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="28" height="28" aria-hidden="true" class="text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <div class="flex flex-col text-left">
                            <span>Authentik SSO</span>
                            <span class="text-xs font-medium text-indigo-100/90">Recommended for GSP core staff</span>
                        </div>
                    </div>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" class="transition group-hover:translate-x-1">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>

                <a
                    href="{{ route('login.steam', ['mode' => 'contact']) }}"
                    class="group flex items-center justify-between gap-4 rounded-2xl border border-white/20 bg-white/10 px-6 py-5 text-base font-semibold text-white shadow-lg shadow-slate-900/40 transition hover:-translate-y-1 hover:bg-white/15"
                >
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/15">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="28" height="28" aria-hidden="true" class="text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3a2 2 0 00-2 2v4c0 1.268.438 2.432 1.17 3.35A4 4 0 009 20h5a7 7 0 007-7V5a2 2 0 00-2-2H5z" />
                                <circle cx="15.5" cy="8.5" r="2.5" />
                            </svg>
                        </span>
                        <div class="flex flex-col text-left">
                            <span>Steam — Tenant Contacts</span>
                            <span class="text-xs font-medium text-slate-100/80">For verified organisation representatives</span>
                        </div>
                    </div>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" class="transition group-hover:translate-x-1">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>

                <a
                    href="{{ route('login.steam', ['mode' => 'player']) }}"
                    class="group flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/70 px-6 py-5 text-base font-semibold text-white shadow-lg shadow-slate-900/40 transition hover:-translate-y-1 hover:bg-slate-900/80"
                >
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="28" height="28" aria-hidden="true" class="text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 20a5.5 5.5 0 0111 0" />
                            </svg>
                        </span>
                        <div class="flex flex-col text-left">
                            <span>Steam — Players</span>
                            <span class="text-xs font-medium text-slate-100/80">For community members following their tickets</span>
                        </div>
                    </div>
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" class="transition group-hover:translate-x-1">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-xs text-slate-300/80">
                <p class="font-semibold uppercase tracking-widest text-slate-200/90">Need an account?</p>
                <p class="mt-2 leading-relaxed text-slate-300/90">
                    Contact the operations team to be onboarded as an authorised tenant contact, or work with your server owner to link your Steam profile as a player. For urgent access issues email
                    <a href="mailto:support@gsp.local" class="text-indigo-200 underline underline-offset-4 hover:text-indigo-100">support@gsp.local</a>.
                </p>
            </div>
        </section>
    </div>
</x-guest-layout>
