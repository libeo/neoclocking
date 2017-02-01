<?php

namespace NeoClocking\Console\Commands;

use Illuminate\Console\Command;
use NeoClocking\Repositories\TaskRepository;
use Symfony\Component\Console\Helper\ProgressBar;

class UpdateTaskLoggedTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:update-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the logged time on all tasks.';

    /**
     * @var TaskRepository
     */
    protected $tasks;

    /**
     * Create a new command instance.
     *
     * @param TaskRepository $tasks
     */
    public function __construct(TaskRepository $tasks)
    {
        parent::__construct();

        $this->tasks = $tasks;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tasks = $this->tasks->findAll();

        $progress = $this->createProgressBar($tasks->count());

        foreach ($tasks as $task) {
            $task->logged_time = 0;
            $task->save();
            $progress->advance(1);
        }

        $progress->finish();

        $this->info("\nTask logged time updated.");
    }

    protected function createProgressBar($max)
    {
        $this->output->writeln('');

        $format = "  - Updating <info>Task</info>: <comment>%current%/%max%</comment> ";
        $format .= "[<comment>%percent%%</comment>]\n    %remaining% %memory:6s%";

        $progressBar = new ProgressBar($this->output, $max);
        $progressBar->setFormat($format);
        $progressBar->start();

        return $progressBar;
    }
}
