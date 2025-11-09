<x-app-layout>
    <x-slot name="header">
        Profile
    </x-slot>

    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Profile Information') }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ __('Name') }}</label>
                        <p class="form-control-static">{{ Auth::user()->name }}</p>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Email') }}</label>
                        <p class="form-control-static">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Delete Account') }}</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
