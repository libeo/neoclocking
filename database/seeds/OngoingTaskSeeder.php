<?php
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\LogEntryRepository;
use NeoClocking\Repositories\UserRepository;

class OngoingTaskSeeder extends Seeder
{
    public function run() {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->make(UserRepository::class);
        /** @var LogEntryRepository $logEntryRepo */
        $logEntryRepo = $this->container->make(LogEntryRepository::class);

        $task = Task::where('number', '=', '1337')->first();

        $log = new LogEntry();
        $log->user_id = $userRepo->findOneByUsername('test')->id;
        $log->task_id = $task->id;
        $log->started_at = Carbon::parse('2015-02-02 08:30:00');
        $log->ended_at = null;
        $logEntryRepo->saveOrUpdate($log);
    }
}
