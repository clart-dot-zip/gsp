<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function index(): View
    {
        return view('admin.access.index', [
            'users' => User::with('groups')->orderBy('name')->get(),
            'groups' => Group::with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function attachGroup(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
        ]);

        $user->groups()->syncWithoutDetaching([$data['group_id']]);

        return Redirect::route('admin.access.index')->with('status', 'Group assigned to user.');
    }

    public function detachGroup(User $user, Group $group): RedirectResponse
    {
        $user->groups()->detach($group->id);

        return Redirect::route('admin.access.index')->with('status', 'Group removed from user.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug ?: Str::slug(Str::random(8));
        $suffix = 1;

        while (Group::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        Group::create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return Redirect::route('admin.access.index')->with('status', 'Group created.');
    }

    public function syncPermissions(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $group->permissions()->sync($data['permissions'] ?? []);

        return Redirect::route('admin.access.index')->with('status', 'Group permissions updated.');
    }
}
