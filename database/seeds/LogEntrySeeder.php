<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\LogEntryRepository;
use NeoClocking\Repositories\UserRepository;

class LogEntrySeeder extends Seeder
{
    public function run()
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->make(UserRepository::class);
        /** @var LogEntryRepository $logEntryRepo */
        $logEntryRepo = $this->container->make(LogEntryRepository::class);

        $task = Task::where('number', '=', '1337')->first();

        $log = new LogEntry();
        $log->user_id = $userRepo->findOneByUsername('test')->id;
        $log->task_id = $task->id;
        $log->started_at = Carbon::parse('2015-01-01 08:30:00');
        $log->ended_at = Carbon::parse('2015-01-01 16:00:00');
        $logEntryRepo->saveOrUpdate($log);

        $log = new LogEntry();
        $log->user_id = $userRepo->findOneByUsername('test')->id;
        $log->task_id = $task->id;
        $log->started_at = Carbon::parse('2014-11-11 08:30:00');
        $log->ended_at = Carbon::parse('2014-11-11 16:00:00');
        $log->comment = 'Some Random Comment';
        $logEntryRepo->saveOrUpdate($log);

        $log = new LogEntry();
        $log->user_id = $userRepo->findOneByUsername('test')->id;
        $log->task_id = $task->id;
        $log->started_at = Carbon::parse("2014-11-12 08:30:00");
        $log->ended_at = Carbon::parse("2014-11-12 09:33:00");
        $log->comment = 'Another Random Comment';
        $logEntryRepo->saveOrUpdate($log);
    }
}
