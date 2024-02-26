<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    
    public function run()
    {
        $user = User::create(['name' => 'Super Admin',
            'email' => 'superadmin@agriwings.com',
            'role' => 'super admin',
            'password' => bcrypt('12345678'),
            'text_password' => '12345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::create(['name' => 'super admin']);

        $permissions = Permission::pluck('id', 'id')->all();

        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);
    }
}
