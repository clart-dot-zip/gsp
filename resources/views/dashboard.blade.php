<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">{{ __('Dashboard') }}</h1>
    </x-slot>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ now()->format('M') }}</h3>
                    <p>Current Month</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ Auth::user()->name }}</h3>
                    <p>Signed In User</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ now()->format('H:i') }}</h3>
                    <p>Server Time</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ config('app.env') }}</h3>
                    <p>Environment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Welcome Back</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ __('You are logged into the AdminLTE powered dashboard. Use the sidebar to navigate between sections and start building your administration experience.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="{{ route('profile.edit') }}" class="d-flex align-items-center">
                                <i class="fas fa-user-cog mr-2"></i> {{ __('Manage Profile') }}
                            </a>
                        </li>
                        <li class="list-group-item">
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger">
                                    <i class="fas fa-sign-out-alt mr-2"></i>{{ __('Sign Out') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
