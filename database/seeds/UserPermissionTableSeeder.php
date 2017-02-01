<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\User;
use NeoClocking\Models\UserPermission;

class UserPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereUsername('libeodap')->first();

        UserPermission::updateOrCreate([
            'user_id' => $user->id,
            'name' => UserPermission::LIBEO_DAP_SYNC,
        ]);
    }
}
