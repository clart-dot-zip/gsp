<?php

use App\Models\Group;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => 'root'],
            ['name' => 'Root Access']
        );

        $adminGroup = Group::firstOrCreate(
            ['slug' => 'administrators'],
            ['name' => 'Administrators']
        );

        if ($adminGroup) {
            $adminGroup->permissions()->syncWithoutDetaching([
                $permission->id,
            ]);

            $allPermissions = Permission::pluck('id')->all();

            if ($allPermissions !== []) {
                $adminGroup->permissions()->syncWithoutDetaching($allPermissions);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('slug', 'root')->first();

        if ($permission) {
            $permission->groups()->detach();
            $permission->delete();
        }
    }
};
