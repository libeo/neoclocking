<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use NeoClocking\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

//        if (config('database.default') != 'sqlite') {
//            DB::statement("TRUNCATE TABLE users, projects, clients, log_entries, user_project_roles, tasks CASCADE");
//        }

        // The order is important!
        $this->call(UserSeeder::class);

        $admin = User::where('username', '=', 'test')->first();

        Auth::login($admin);

        $this->call(ClientSeeder::class);
        $this->call(ProjectSeeder::class);
        $this->call(UserProjectRoleSeeder::class);
        $this->call(TaskSeeder::class);
        $this->call(FavouriteTaskSeeder::class);
        $this->call(LogEntrySeeder::class);
        $this->call(OngoingTaskSeeder::class);

        Auth::logout();

        Model::reguard();
    }
}
