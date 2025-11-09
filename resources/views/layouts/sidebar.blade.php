<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('images/osaka.png') }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name', 'Laravel') }}</span>
    </a>

    <div class="sidebar d-flex flex-column">
        @auth
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <i class="fas fa-user-circle text-white-50 fa-2x"></i>
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block">{{ Auth::user()->name }}</a>
                </div>
            </div>
        @endauth

        @php
            $sidebarUser = Auth::user();
            $canViewTenantPages = $sidebarUser && $sidebarUser->hasPermission('view_tenant_pages');
            $canManageTenants = $sidebarUser && $sidebarUser->hasPermission('manage_tenants');
            $canManageContacts = $sidebarUser && $sidebarUser->hasPermission('manage_contacts');
            $canManageAccess = $sidebarUser && $sidebarUser->hasPermission('manage_access');
        @endphp

        <nav class="mt-2 flex-grow-1 d-flex flex-column">
            <ul class="nav nav-pills nav-sidebar flex-column flex-grow-1" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                @if($canViewTenantPages && isset($tenantPageCategories) && count($tenantPageCategories) > 0)
                    @foreach($tenantPageCategories as $categoryKey => $category)
                        @php
                            $categoryPages = is_array($category['pages'] ?? null) ? $category['pages'] : [];
                        @endphp
                        @continue(empty($categoryPages))
                        @php
                            $categoryTitle = $category['title'] ?? ucwords(str_replace('_', ' ', (string) $categoryKey));
                            $categoryIcon = $category['icon'] ?? 'fas fa-folder';
                            $currentPageKey = request()->routeIs('tenants.pages.show') ? request()->route('page') : null;
                            $categoryIsActive = ! is_null($currentPageKey) && array_key_exists($currentPageKey, $categoryPages);
                        @endphp

                        @if(count($categoryPages) === 1)
                            @php
                                $pageKey = array_key_first($categoryPages);
                                $pageTitle = $categoryPages[$pageKey];
                            @endphp
                            <li class="nav-item">
                                <a
                                    href="{{ empty($currentTenant) ? '#' : route('tenants.pages.show', $pageKey) }}"
                                    class="nav-link {{ $categoryIsActive ? 'active' : '' }} {{ empty($currentTenant) ? 'disabled text-muted' : '' }}"
                                    @if(empty($currentTenant)) aria-disabled="true" tabindex="-1" onclick="return false;" @endif
                                >
                                    <i class="nav-icon {{ $categoryIcon }}"></i>
                                    <p>{{ $categoryTitle }}</p>
                                </a>
                            </li>
                        @else
                            <li class="nav-item has-treeview {{ $categoryIsActive ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $categoryIsActive ? 'active' : '' }}">
                                    <i class="nav-icon {{ $categoryIcon }}"></i>
                                    <p>
                                        {{ $categoryTitle }}
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @foreach($categoryPages as $pageKey => $pageTitle)
                                        <li class="nav-item">
                                            <a
                                                href="{{ empty($currentTenant) ? '#' : route('tenants.pages.show', $pageKey) }}"
                                                class="nav-link {{ request()->routeIs('tenants.pages.show') && request()->route('page') === $pageKey ? 'active' : '' }} {{ empty($currentTenant) ? 'disabled text-muted' : '' }}"
                                                @if(empty($currentTenant)) aria-disabled="true" tabindex="-1" onclick="return false;" @endif
                                            >
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>{{ $pageTitle }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @endforeach
                @endif
                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>
            </ul>
        </nav>
        @if($canManageTenants || $canManageContacts || $canManageAccess)
            <nav class="mt-auto">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item has-treeview {{ request()->routeIs('tenants.manage') || request()->routeIs('tenants.contacts.*') || request()->routeIs('admin.access.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-tools"></i>
                            <p>
                                Admin
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if($canManageTenants)
                                <li class="nav-item">
                                    <a href="{{ route('tenants.manage') }}" class="nav-link {{ request()->routeIs('tenants.manage') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Tenants</p>
                                    </a>
                                </li>
                            @endif
                            @if($canManageContacts)
                                <li class="nav-item">
                                    <a href="{{ $currentTenant ? route('tenants.contacts.index', $currentTenant) : '#' }}" class="nav-link {{ request()->routeIs('tenants.contacts.*') ? 'active' : '' }} {{ empty($currentTenant) ? 'disabled text-muted' : '' }}" @if(empty($currentTenant)) aria-disabled="true" tabindex="-1" onclick="return false;" @endif>
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Manage Contacts</p>
                                    </a>
                                </li>
                            @endif
                            @if($canManageAccess)
                                <li class="nav-item">
                                    <a href="{{ route('admin.access.index') }}" class="nav-link {{ request()->routeIs('admin.access.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Access Control</p>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                </ul>
            </nav>
        @endif
    </div>
</aside>
