<?php

namespace NeoClocking\Console\Commands;

use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTime;
use DateTimeZone;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use NeoClocking\Exceptions\ImportFailedException;
use NeoClocking\Exceptions\SkipRowImportException;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Milestone;
use NeoClocking\Models\Project;
use NeoClocking\Models\ReferenceType;
use NeoClocking\Models\ResourceType;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;
use NeoClocking\Repositories\ReferenceTypeRepository;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Services\ClockingIT\ImportRunner;
use NeoClocking\Services\Ldap\LibeoDataService;
use NeoClocking\Services\Updaters\ClientUpdater;
use NeoClocking\Services\Updaters\ProjectUpdater;
use NeoClocking\Services\Updaters\UserProjectRolesUpdater;
use NeoClocking\Services\Updaters\UserUpdater;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ImportCommand extends Command
{
    const DEFAULT_TIMEZONE = 'UTC';

    /**
     * Tables available for importation
     *
     * @var string[]
     */
    static private $IMPORTS = ['users', 'clients', 'projects', 'tasks', 'milestones', 'logs', 'updates'];

    /**
     * Logs to not import as they are known to have a negative duration
     *
     * @var integer[]
     */
    static private $IGNORED_LOGS = [1377274, 1376576, 1647579, 1450569, 1376745, 1463909];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clocking:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ClockingIT data';

    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var ImportRunner
     */
    protected $runner;

    /**
     * @var ReferenceTypeRepository
     */
    protected $referenceTypeRepository;

    /**
     * @var ResourceTypeRepository
     */
    protected $resourceTypeRepository;

    /**
     * @var LibeoDataService
     */
    protected $dataService;

    /**
     * Create a new console command instance.
     *
     * @param DatabaseManager         $databaseManager
     * @param ReferenceTypeRepository $referenceTypeRepository
     * @param ResourceTypeRepository  $resourceTypeRepository
     * @param LibeoDataService        $dataService
     */
    public function __construct(
        DatabaseManager $databaseManager,
        ReferenceTypeRepository $referenceTypeRepository,
        ResourceTypeRepository $resourceTypeRepository,
        LibeoDataService $dataService
    ) {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->referenceTypeRepository = $referenceTypeRepository;
        $this->resourceTypeRepository = $resourceTypeRepository;
        $this->dataService = $dataService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $connection = $this->argument('database');

        $this->db = $this->databaseManager->connection($connection);

        $this->info('Importing ClockingIT data...');

        $only = explode(',', $this->option('only'));
        $except = explode(',', $this->option('except'));

        $imports = $this->filterImports($only, $except);

        foreach ($imports as $import) {
            $method = 'import' . ucfirst($import);

            $this->$method();
        }

        $this->info("\nImportation complete.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['database', InputArgument::REQUIRED, 'Database connection to import from'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $tables = implode(', ', self::$IMPORTS);

        return [
            ['only', null, InputOption::VALUE_OPTIONAL, "Import only the given tables ({$tables})", ''],
            ['except', null, InputOption::VALUE_OPTIONAL, "Import all except the given tables ({$tables})", ''],
        ];
    }

    /**
     * @param string $out
     */
    private function progressOutput($out)
    {
        $this->info("\n{$out}\n");
    }

    /**
     * Prints a message in orange
     *
     * @param string $msg
     */
    private function printWarning($msg)
    {
        $this->comment("\n{$msg}\n");
    }

    /**
     * Import users from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importUsers()
    {
        $this->dataService->preloadUsers();
        $this->dataService->preloadWorkCategories();

        $updateFunction = function ($userData) {
            $username = $userData->username;

            // Convert all times to current timezone
            $gmt = new DateTimeZone('GMT');
            $current_tz = new DateTimeZone(self::DEFAULT_TIMEZONE);
            $userData->{'created_at'} = (new DateTime($userData->{'created_at'}, $gmt))->setTimezone($current_tz);

            $userUpdater = app(UserUpdater::class, [
                [
                    'username'    => $username,
                    'clocking_id' => $userData->id,
                    'created_at'  => $userData->{'created_at'},
                    'updated_at'  => Carbon::now(),
                ],
            ]);
            $success = $userUpdater->update();

            if (!$success) {
                $this->printWarning(
                    "Failed to find user \"$username\" in LDAP; creating or updating with default values."
                );
                $success = $userUpdater->createWithDefaults();
                if (!$success) {
                    throw new SkipRowImportException(
                        'User',
                        "Unable to add the user \"{$username}\". An error occurred."
                    );
                }
            }
        };

        $this->runImportOrUpdate('users', 'username', $updateFunction);
    }

    /**
     * Import clients (customers) from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importClients()
    {
        $this->dataService->preloadClients();

        $updateFunction = function ($clientData) {
            $number = $clientData->{'customer_number'};

            // Convert all times to current timezone
            $gmt = new DateTimeZone('GMT');
            $current_tz = new DateTimeZone('UTC');
            $clientData->{'created_at'} = (new DateTime($clientData->{'created_at'}, $gmt))->setTimezone($current_tz);

            $id = $clientData->id;
            $createdAt = $clientData->{'created_at'};
            $clientUpdater = new ClientUpdater(
                $number,
                [
                    'clocking_id' => $clientData->id,
                    'created_at'  => $createdAt,
                ]
            );
            $success = $clientUpdater->update();

            if (!$success) {
                $this->printWarning(
                    "No client found with a number of \"{$number}\" in LDAP; creating or updating manually."
                );
                $clientData = $this->db->table('customers')
                    ->select('name')
                    ->where('id', '=', $id)
                    ->first();
                $name = $clientData->name;
                $success = $clientUpdater->manualUpdate(
                    $name,
                    $number,
                    $id,
                    $createdAt
                );
                if (!$success) {
                    throw new SkipRowImportException(
                        'Client',
                        "Unable to add Client \"{$name}\" with the number \"{$number}\". An error occurred."
                    );
                }
            }
        };
        $this->runImportOrUpdate('customers', 'customer_number', $updateFunction);
    }

    /**
     * Import projects from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importProjects()
    {
        $this->dataService->preloadProjects();

        $updateFunction = function ($project) {
            $projectNumber = $project->{'project_number'};

            $cit = $this->db->table('projects')->where('id', $project->id)->first();

            // Convert all times to current timezone
            $gmt = new DateTimeZone('GMT');
            $current_tz = new DateTimeZone('UTC');
            $project->{'created_at'} = (new DateTime($project->{'created_at'}, $gmt))->setTimezone($current_tz);

            $projectUpdater = app(ProjectUpdater::class, [
                [
                    'number' => $projectNumber,
                    'clocking_id' => $project->id,
                    'type' => $cit->project_type,
                    'created_at' => $project->{'created_at'},
                    'updated_at' => Carbon::now(),
                ],
            ]);


            $success = $projectUpdater->update();
            if (!$success) {
                throw new SkipRowImportException(
                    'Project',
                    "Unable to add Project with the number \"{$projectNumber}\". An error occurred."
                );
            }

            $userProjectRoleUpdater = new UserProjectRolesUpdater();
            $success = $userProjectRoleUpdater->updateByProject($projectNumber);
            if (!$success) {
                $errorMsg = 'An error occurred whilst updating User/Project/Role';
                $errorMsg .= "relationships for the project \"{$projectNumber}\".";

                throw new SkipRowImportException('UserProjectRole', $errorMsg);
            }
        };
        $this->runImportOrUpdate('projects', 'project_number', $updateFunction);
    }

    /**
     * Import tasks from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importTasks()
    {
        $modelFactory = function () {
            return new Task;
        };
        $targetTable = 'tasks';
        $sourceTable = 'tasks';
        $columns = [
            'name'             => 'name',
            'number'           => 'task_num',
            'estimation'       => 'duration',
            'clocking_id'      => 'id',
            'active'           => 'status',
            'project_id'       => 'project_id',
            'created_at'       => 'created_at',
            'resource_type_id' => 'created_at',
        ];
        $keys = [
            'number',
        ];

        // Prefetch all projects
        $projectsCache = [];
        /** @var Project[] $allProjects */
        $allProjects = Project::all();
        foreach ($allProjects as $project) {
            $projectsCache[$project->clocking_id] = $project->id;
        }

        /**
         * The previous ClockingIt system has certain tasks with the same "task_num". This is problematic
         * since a task's "number" field is now a unique identifier and thus duplicates cannot be imported.
         * To prevent data loss, the first one of each task number will be imported, but any others
         * will be added to a list and imported at the end with a new task number
         * If needed, they can still be found via their "clocking_id" field.
         *
         * Issue: https://projets.libeo.com/issues/37324
         */
        $findDuplicatesQuery = 'SELECT GROUP_CONCAT(id) AS ids,
                                       task_num,
                                       COUNT(*) AS quantity
                                FROM tasks
                                GROUP BY task_num
                                HAVING quantity > 1
                                ORDER BY id';

        $duplicateTasks = [];
        $importedDuplicateTasks = [];

        $allDuplicateTasks = $this->db->select($findDuplicatesQuery);
        foreach ($allDuplicateTasks as $duplicateTask) {
            $ids = explode(',', $duplicateTask->ids);
            $taskNum = $duplicateTask->{'task_num'};
            foreach (range(0, $duplicateTask->quantity - 1) as $i) {
                $duplicateTasks[$ids[$i]] = $taskNum;
            }
        }

        $referenceTypeRedmineId = $this->referenceTypeRepository->findOneByCode(ReferenceType::CODE_REDMINE)->id;

        $resourcesToFetch = [
            ResourceType::CODE_OTHER,
            'formation',
            'strategie',
            'wireframes',
            'design',
            'integration',
            'programmation',
            'sysadmin',
            'gestion_de_projet',
            'qa',
            'direction_artistique',
        ];
        $resourceCache = [];
        foreach ($resourcesToFetch as $resourceCode) {
            $resourceCache[$resourceCode] = $this->resourceTypeRepository->findOneByCode($resourceCode)->id;
        }

        // Recover tasks' tags from another table in Clocking for future reference.
        $citResourcesTagsQuery = $this->db->table('task_tags')->select('*');

        $clockingResourcesTypesTagsRaw = $citResourcesTagsQuery->get();
        $clockingResourcesTypesTags = [];
        foreach ($clockingResourcesTypesTagsRaw as $row) {
            $clockingResourcesTypesTags[$row->{'task_id'}] = $row->{'tag_id'};
        }

        $endMerge = function ($data) use (
            $projectsCache,
            $clockingResourcesTypesTags,
            $resourceCache,
            $referenceTypeRedmineId,
            &$duplicateTasks,
            &$importedDuplicateTasks
        ) {
            $citId = $data['clocking_id'];

            /*
             * Verify if the current task is in the list of duplicate tasks
             */
            if (array_key_exists($citId, $duplicateTasks)) {
                $taskNum = $duplicateTasks[$citId];

                /*
                 * Verify that, if this task was previously imported and assigned a new task number,
                 * we do not try to import it normally.
                 */
                $exactExists = Task::whereClockingIdAndNumber($citId, $taskNum)->exists();
                $similarExists = Task::whereNumber($taskNum)->exists();
                if ((!$exactExists && $similarExists) || in_array($taskNum, $importedDuplicateTasks, true)) {
                    throw new SkipRowImportException(
                        'Task',
                        "Task with CIT-ID of {$citId} has the same task number ({$taskNum}) as another task."
                    );
                }

                /*
                 * This task can be imported normally because either no task with this task number
                 * has been imported before or it was previously imported normally.
                 *
                 * Remove this task from the task's ID from the list to be processed at the end
                 * And add its task number to the list of ones already imported
                 */
                unset($duplicateTasks[$citId]);
                $importedDuplicateTasks[] = $taskNum;
            }

            $taskIsActive = $this->getIsActive($data['active']);

            $clockingProjectId = $data['project_id'];
            if (array_key_exists($clockingProjectId, $projectsCache)) {
                $projectId = $projectsCache[$clockingProjectId];
            } else {
                throw new SkipRowImportException(
                    'Task',
                    "Unable to add Task since no project with the CIT-ID {$clockingProjectId} was found",
                    $data
                );
            }

            $estimation = null;
            if (array_key_exists('estimation', $data) && $data['estimation']) {
                $estimation = $data['estimation'];
            }

            $clockingResourceId = null;
            if (array_key_exists($citId, $clockingResourcesTypesTags)) {
                $clockingResourceId = $clockingResourcesTypesTags[$citId];
            }

            $resourceType = $this->getResourceTypeId($clockingResourceId, $resourceCache);

            /*
             * reference_number
             * reference_type_id
             */
            $referenceNumber = null;
            $referenceTypeId = null;

            // Redmine Issue prefixed by "#"
            $matches = [];
            preg_match_all('(#[0-9]{5})', $data['name'], $matches);
            if (count($matches) === 1 && count($matches[0]) === 1) {
                $match = $matches[0][0];
                $redmineIssueNumber = ltrim($match, '#');
                $referenceNumber = $redmineIssueNumber;
                $referenceTypeId = $referenceTypeRedmineId;
            }

            return [
                'active'             => $taskIsActive,
                'project_id'         => $projectId,
                'estimation'         => $estimation,
                'resource_type_id'   => $resourceType,
                'reference_number'   => $referenceNumber,
                'reference_type_id'  => $referenceTypeId,
                'revised_estimation' => null,
            ];
        };

        $beforeRowHook = function (&$data) {
            // Convert all times to current timezone
            $gmt = new DateTimeZone('GMT');
            $current_tz = new DateTimeZone(self::DEFAULT_TIMEZONE);

            $data['created_at'] = (new DateTime($data['created_at'], $gmt))->setTimezone($current_tz);
        };

        $afterRowHook = function () {
            //Update task_number_seq to the highest number to be sure future auto increments will work
            DB::statement("SELECT setval('task_number_seq', (SELECT MAX(number) FROM tasks))");
        };

        $afterImportCompleted = function () use (
            $projectsCache,
            $clockingResourcesTypesTags,
            $resourceCache,
            $referenceTypeRedmineId,
            &$duplicateTasks
        ) {
            /*
             * Loop through and import all tasks remaining in the list of duplicates
             * and import them with a new task number.
             */
            $this->progressOutput('Commencing import of duplicate Tasks');
            $this->printWarning(
                'These tasks will be assigned new Task Numbers, but will be retraceable by means of their clocking_id'
            );
            $total = count($duplicateTasks);
            $iteration = 1;
            foreach ($duplicateTasks as $citId => $taskNumber) {
                /**
                 * Check first if it was previously imported so as to avoid duplicates
                 * @var Task $task
                 */
                $task = Task::query()->where('clocking_id', '=', $citId)->first();
                if (!$task || !$task->exists) {
                    $task = new Task();
                }
                $this->output->write("\x0DImporting duplicate task {$iteration} of {$total} (CIT: $citId)");
                $iteration++;

                $oldData = (array)$this->db->selectOne("SELECT * FROM tasks WHERE id={$citId}");

                $newTaskData = [
                    'clocking_id' => $citId,
                    'name'        => $oldData['name'],
                    'created_at'  => $oldData['created_at'],
                    'updated_at'  => $oldData['updated_at'],
                    'active'      => $this->getIsActive($oldData['status'])
                ];

                $clockingProjectId = $oldData['project_id'];
                if (array_key_exists($clockingProjectId, $projectsCache)) {
                    $newTaskData['project_id'] = $projectsCache[$clockingProjectId];
                }

                if (!empty($oldData['estimation'])) {
                    $newTaskData['estimation'] = $oldData['estimation'];
                }

                $clockingResourceId = null;
                if (array_key_exists($citId, $clockingResourcesTypesTags)) {
                    $clockingResourceId = $clockingResourcesTypesTags[$citId];
                }

                $newTaskData['resource_type_id'] = $this->getResourceTypeId($clockingResourceId, $resourceCache);

                // Redmine Issue prefixed by "#"
                $matches = [];
                preg_match_all('(#[0-9]{5})', $oldData['name'], $matches);
                if (count($matches) === 1 && count($matches[0]) === 1) {
                    $match = $matches[0][0];
                    $redmineIssueNumber = ltrim($match, '#');
                    $newTaskData['reference_number'] = $redmineIssueNumber;
                    $newTaskData['reference_type_id'] = $referenceTypeRedmineId;
                }

                Model::unguard();
                $task->fill($newTaskData);
                $success = $task->save();
                Model::reguard();
                if (!$success) {
                    throw new SkipRowImportException(
                        'DuplicateTask',
                        'Failed to create new Task'
                    );
                }
            }
            $this->progressOutput('Finished importing duplicate Tasks');
        };

        $this->runImport(
            $modelFactory,
            $targetTable,
            $sourceTable,
            $columns,
            $keys,
            [],
            $endMerge,
            $beforeRowHook,
            $afterRowHook,
            $afterImportCompleted
        );
    }

    /**
     * Determine if project is active based on previous status
     * @param int|null $oldStatusId
     * @return boolean
     */
    private function getIsActive($oldStatusId)
    {
        $oldActiveStatuses = [
            0, // Unknown... Will consider it as open since recent tasks use this status...
            1, // Open
            2, // In Progress
        ];

        // True: Unknown, Open or In progress shall be considered "Active"
        // False: Null or other
        return in_array($oldStatusId, $oldActiveStatuses, true);
    }

    /**
     * Get the resource_id from the cache
     *
     * @param $clockingResourceId
     * @param $resourceCache
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function getResourceTypeId($clockingResourceId, $resourceCache)
    {
        switch ($clockingResourceId) {
            case 51: //Formation
                $typeCode = 'formation';
                break;

            case 55: //Stratégie
                $typeCode = 'strategie';
                break;

            case 57: //Wireframes
                $typeCode = 'wireframes';
                break;

            case 59: //Design
                $typeCode = 'design';
                break;

            case 61: //Intégration
                $typeCode = 'integration';
                break;

            case 63: //Programmation
                $typeCode = 'programmation';
                break;

            case 65: //Sysadmin
                $typeCode = 'sysadmin';
                break;

            case 67: //Gestion de projet
                $typeCode = 'gestion_de_projet';
                break;

            default:
                // All others fallback here.
                $typeCode = ResourceType::CODE_OTHER;
                break;
        }
        if (!array_key_exists($typeCode, $resourceCache)) {
            throw new ImportFailedException("The Resource Type with code {$typeCode} is not in cache map");
        }
        return $resourceCache[$typeCode];
    }

    /**
     * Import milestones (tags) from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importMilestones()
    {
        $modalFactory = function () {
            return new Milestone;
        };

        $query = function () {
            return $this->db->table('task_tags')
                 ->join('tasks', 'task_tags.task_id', '=', 'tasks.id')
                 ->join('tags', 'task_tags.tag_id', '=', 'tags.id')
                 ->groupBy('tasks.id')
                 ->selectRaw('GROUP_CONCAT(tags.name SEPARATOR \',\') AS name')
                 ->addSelect('tasks.project_id');
        };

        /** @var Collection $rows */
        $rows = $query()->addSelect('tasks.id')->get();
        $projects = Project::all()->lists('id', 'clocking_id');

        $taskIdsByProject = [];
        foreach ($rows as $row) {
            $projectId = $projects->get($row->project_id);
            $key = md5("{$row->name}:{$projectId}");
            if (! array_key_exists($key, $taskIdsByProject)) {
                $taskIdsByProject[$key] = [];
            }
            $taskIdsByProject[$key][] = $row->{'id'};
        }

        $endMerge = function ($data) use ($projects) {
            $projectId = $projects->get($data['project_id']);
            $data['project_id'] = $projectId;
            return $data;
        };

        $linkWithTask = function ($data, Milestone $milestone) use ($taskIdsByProject) {
            $taskIds = $taskIdsByProject[md5("{$data['name']}:{$data['project_id']}")];
            Task::whereIn('clocking_id', $taskIds)->update(['milestone_id' => $milestone->id]);
        };

        $this->runImport(
            $modalFactory,
            'milestones',
            null,
            ['name' => 'name', 'project_id' => 'project_id'],
            ['name', 'project_id'],
            [],
            $endMerge,
            null,
            $linkWithTask,
            null,
            $query()
        );
    }

    /**
     * Import working logs from ClockingIT to the new application.
     *
     * @return void
     */
    protected function importLogs()
    {
        $modelFactory = function () {
            return new LogEntry;
        };
        $targetTable = 'log_entries';
        $targetSource = 'work_logs';
        $columns = [
            'hourly_cost' => 'cost',
            'validated'   => 'approved',
            'comment'     => 'body',
            'started_at'  => 'started_at',
            'ended_at'    => 'duration',
            'clocking_id' => 'id',
            'user_id'     => 'user_id',
            'task_id'     => 'task_id',
        ];
        $keys = [
            'clocking_id',
        ];

        // Prefetch all users
        $usersCache = [];
        /** @var User[] $allUsers */
        $allUsers = User::all(['id', 'clocking_id']);
        foreach ($allUsers as $user) {
            $usersCache[$user->clocking_id] = $user->id;
        }

        // Prefetch all tasks
        $tasksCache = [];
        /** @var Task[] $allTasks */
        $allTasks = Task::all(['id', 'clocking_id']);
        foreach ($allTasks as $task) {
            $tasksCache[$task->clocking_id] = $task->id;
        }

        // Convert all times to current timezone
        $gmt = new DateTimeZone('GMT');
        $current_tz = new DateTimeZone('UTC');

        $endMerge = function ($data) use ($usersCache, $tasksCache, $gmt, $current_tz) {
            // Ignore log with a negative duration
            if ($data['ended_at'] < 0) {
                $e = new SkipRowImportException(
                    'WorkLog',
                    "WorkLog with the CIT-ID of {$data['clocking_id']} has a negative duration ({$data['ended_at']}).",
                    $data
                );
                if (in_array($data['clocking_id'], self::$IGNORED_LOGS, true)) {
                    // Avoid excessive noise in log if log is known to be problematic.
                    $e->setSilent(true);
                }

                throw $e;
            }

            /**
             * comment
             */
            $comment = trim($data['comment']);
            if ($comment === '') {
                $comment = null;
            }

            /**
             * cost
             * Convert to cents.
             */
            $data['hourly_cost'] *= 100;

            /**
             * end
             */
            $date = (new DateTime($data['started_at'], $gmt))->setTimezone($current_tz);
            $end = (new DateTime($data['started_at'], $gmt))->add((new DateInterval('PT'.$data['ended_at'].'S')));

            /**
             * user_id
             */
            $clockingId = $data['user_id'];
            if (array_key_exists($clockingId, $usersCache)) {
                $userId = $usersCache[$clockingId];
            } else {
                throw new SkipRowImportException(
                    'LogEntry',
                    "Unable to add LogEntry since no user with the CIT-ID {$clockingId} was found",
                    $data
                );
            }

            /**
             * task_id
             */
            $clockingTaskId = $data['task_id'];
            if (array_key_exists($clockingTaskId, $tasksCache)) {
                $taskId = $tasksCache[$clockingTaskId];
            } else {
                throw new SkipRowImportException(
                    'LogEntry',
                    "Unable to add LogEntry since no task with the CIT-ID {$clockingTaskId} was found",
                    $data
                );
            }

            /**
             * validated
             */
            if ($data['validated'] !== null) {
                $validated = ($data['validated'] === 1);
            } else {
                // Everything made earlier than the last 2 weeks is auto-approved
                $validated = Carbon::instance($date)->addWeeks(2)->isPast();
            }

            return [
                'comment'    => $comment,
                'ended_at'   => $end,
                'started_at' => $date,
                'user_id'    => $userId,
                'task_id'    => $taskId,
                'validated'  => $validated,
            ];
        };

        $this->runImport(
            $modelFactory,
            $targetTable,
            $targetSource,
            $columns,
            $keys,
            // Certains log de temps de ClockingIT ne sont pas de vraies entrées de temps. Elle ont le duration à 0
            ['duration' => ['<>', 0]],
            $endMerge
        );
    }

    protected function importUpdates()
    {
        $touchFunction = function ($task) {
            $results = DB::select(
                'SELECT TRUNC(EXTRACT(EPOCH FROM SUM(l.ended_at - l.started_at))/60) as minutes
                 FROM log_entries l
                 WHERE l.task_id = :id',
                ['id' => $task->id]
            );

            Task::whereId($task->id)->update(['logged_time' => $results[0]->minutes]);
        };

        $this->info('Touch logs to updates times');
        $this->runner = new ImportRunner($this->output, $this->db);
        $this->runner->runOnModel(Task::query(), $touchFunction);
        $this->runner = null;
        $this->info("\n\nFinished touching logs.\n");
    }

    /**
     * Run the importation for given values.
     *
     * @param Closure $modelFactory
     * @param string  $targetTable
     * @param string  $sourceTable
     * @param array   $columns
     * @param array   $keys
     * @param array   $selectConditions     ['columnName' => ['operator', 'value']]
     * @param Closure $endMerge
     * @param Closure $beforeRowHook
     * @param Closure $afterRowHook
     * @param Closure $afterImportCompleted Function to be executed after import has been completed
     * @param Builder $query
     */
    protected function runImport(
        Closure $modelFactory,
        $targetTable,
        $sourceTable,
        array $columns,
        array $keys,
        array $selectConditions = [],
        Closure $endMerge = null,
        Closure $beforeRowHook = null,
        Closure $afterRowHook = null,
        Closure $afterImportCompleted = null,
        Builder $query = null
    ) {
        $this->runner = new ImportRunner($this->output, $this->db);
        $this->runner->setModelFactory($modelFactory);
        $this->runner->setKeys($keys);
        $this->runner->setColumns($columns);
        $this->runner->setConditions($selectConditions);
        $this->runner->setTable($targetTable, $sourceTable);
        $this->runner->setEndMerge($endMerge);
        $this->runner->setBeforeRowHook($beforeRowHook);
        $this->runner->setAfterRowHook($afterRowHook);
        $this->runner->setAfterImportCompleted($afterImportCompleted);
        $this->runner->setQuery($query);

        $this->runner->run();
    }

    /**
     * Import data using updaters instead
     *
     * @param $table
     * @param $identifier
     * @param $updateFunction
     */
    private function runImportOrUpdate($table, $identifier, $updateFunction)
    {
        $title = ucfirst($table);
        $this->info("Commencing import of {$title}");
        $this->runner = new ImportRunner($this->output, $this->db);
        $this->runner->runImportQuery($table, $identifier, $updateFunction);
        $this->runner = null;
        $this->info("\n\nFinished importing {$title}\n");
    }

    /**
     * Prepare the imports to run.
     *
     * @param array $only
     * @param array $except
     *
     * @return string[]
     */
    protected function filterImports(array $only, array $except)
    {
        array_walk($only, function (&$value) {
            $value = trim($value);
        });

        array_walk($except, function (&$value) {
            $value = trim($value);
        });

        // Remove empty values
        $only = array_filter($only);
        $except = array_filter($except);

        $imports = self::$IMPORTS;

        if (count($only) > 0) {
            $imports = array_filter($imports, function ($value) use ($only) {
                return in_array($value, $only, true);
            });
        }

        if (count($except) > 0) {
            $imports = array_filter($imports, function ($value) use ($except) {
                return !in_array($value, $except, true);
            });
        }

        return $imports;
    }
}
