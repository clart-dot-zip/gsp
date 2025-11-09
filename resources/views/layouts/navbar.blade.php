<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
        @auth
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <i class="far fa-user mr-2"></i>
                    <span>{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-primary">
                        <i class="fas fa-user-circle fa-3x mb-2"></i>
                        <p class="mb-0">
                            {{ Auth::user()->name }}
                            <small>{{ Auth::user()->email }}</small>
                        </p>
                    </li>
                    <li class="user-footer d-flex justify-content-between">
                        <a href="{{ route('profile.edit') }}" class="btn btn-default btn-flat">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-default btn-flat text-danger">
                                <i class="fas fa-sign-out-alt mr-2"></i>Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        @endauth
    </ul>
</nav>
