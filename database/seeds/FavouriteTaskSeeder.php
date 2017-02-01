<?php
use Illuminate\Database\Seeder;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\UserRepository;

class FavouriteTaskSeeder extends Seeder
{
    public function run() {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->make(UserRepository::class);

        $task = Task::where('number', '=', '1337')->first()->id;

        $userRepo->findOneByUsername('test')->favouriteTasks()->detach($task);
        $userRepo->findOneByUsername('test')->favouriteTasks()->attach($task);
    }

}
