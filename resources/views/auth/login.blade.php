<x-guest-layout>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Sign in</h1>
        <p class="mt-2 text-sm text-gray-600">Choose the authentication method that applies to you.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-4">
        <form method="GET" action="{{ route('login.authentik') }}">
            <x-primary-button class="w-full justify-center gap-2 text-sm">
                <span class="font-semibold">Authentik SSO</span>
                <span class="text-xs font-normal text-gray-200">(Administrators)</span>
            </x-primary-button>
        </form>

        <form method="GET" action="{{ route('login.steam') }}">
            <x-secondary-button class="w-full justify-center gap-2 text-sm">
                <span class="font-semibold">Steam Login</span>
                <span class="text-xs font-normal text-gray-700">(Tenant Contacts)</span>
            </x-secondary-button>
        </form>
    </div>
</x-guest-layout>
