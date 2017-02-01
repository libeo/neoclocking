<?php

namespace NeoClocking\Console\Commands;

use Illuminate\Console\Command;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use SearchIndex;
use Symfony\Component\Console\Helper\ProgressBar;

class BuildSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neoclocking:buildSearchIndex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Rebuild l'index Elasticsearch.";

    private $progress;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function progress($label, $count)
    {
        $this->progress = $this->output->createProgressBar($count);
        $format = "- Indexing <info>$label</info>:<comment>%current%/%max%</comment>[<comment>%percent%%</comment>]";
        $format .= "\n    %elapsed%/%estimated% %memory:6s%";
        $this->progress->setFormat($format);
        $this->line("\n");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->tasks();
        $this->clients();
        $this->projects();
        $this->progress->finish();
        $this->line("\n");
    }

    private function tasks()
    {
        $client = SearchIndex::getClient();

        $myTypeMapping = [
            'properties' => [
                'task_number' => [
                    'type' => 'string',
                ],
                'task_name' => [
                    'type' => 'string',
                    'analyser' => 'standard',
                    'fields' => [
                        'folded' => [
                            'type' => 'string',
                            'analyzer' => 'folding',
                        ],
                    ],
                ],
                'project_name' => [
                    'type' => 'string',
                    'analyser' => 'standard',
                    'fields' => [
                        'folded' => [
                            'type' => 'string',
                            'analyzer' => 'folding',
                        ],
                    ],
                ],
                'project_client_name' => [
                    'type' => 'string',
                    'analyser' => 'standard',
                    'fields' => [
                        'folded' => [
                            'type' => 'string',
                            'analyzer' => 'folding',
                        ],
                    ],
                ],
            ],
        ];

        $settings = [
            'analysis' => [
                'analyzer' => [
                    'folding' => [
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
            ],
        ];

        try {
            $deleteParams['index'] = 'main';
            $client->indices()->delete($deleteParams);
        } catch (\Exception $e) {}

        $params['body']['settings'] = $settings;
        $params['body']['mappings']['task'] = $myTypeMapping;
        $params['index'] = 'main';
        $client->indices()->create($params);

        $tasks = Task::all();

        $this->progress('Tasks', $tasks->count());

        foreach ($tasks as $task) {
            SearchIndex::upsertToIndex($task);
            $this->progress->advance();
        }
    }

    private function clients()
    {
        $clients = Client::all();
        $this->progress('Clients', $clients->count());
        foreach ($clients as $client) {
            SearchIndex::upsertToIndex($client);
            $this->progress->advance();
        }
    }

    private function projects()
    {
        $projects = Project::all();
        $this->progress('Projects', $projects->count());
        foreach ($projects as $project) {
            SearchIndex::upsertToIndex($project);
            $this->progress->advance();
        }
    }
}
