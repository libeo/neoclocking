<?php

namespace NeoClocking\Repositories;

use Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;
use SearchIndex;

class TaskRepository
{
    /**
     * @return Task[]
     */
    public function findAll()
    {
        return Task::all();
    }

    /**
     * @param Project $project
     * @return Task[]
     */
    public function findByProject(Project $project)
    {
        return Task::whereProjectId($project->id)->get();
    }

    /**
     * @param integer $id
     * @return Task
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return Task::whereId($id)->first();
    }

    /**
     * @param Task $task
     * @return bool
     * @throws UserNotAuthorisedException
     * @throws ModelValidationException
     */
    public function save(Task $task)
    {
        if (user()->cannot('update', $task)) {
            throw new UserNotAuthorisedException(
                "You are not authorized to edit the task #{$task->number}."
            );
        }

        $task->logEntries()->touch();

        $taskNumber = $task->number;
        $taskNumberSpecified = isset($taskNumber);
        $success = $task->save();
        //TODO : Verifier que c'est "thread safe" comme fonctionnement
        if ($taskNumberSpecified && $success) {
            $this->updateTaskNumberSequence();
        }
        return $success;
    }

    /**
     * @param Task $task
     *
     * @return bool
     *
     * @throws UserNotAuthorisedException
     * @throws \Exception
     */
    public function delete(Task $task)
    {
        if (user()->cannot('update', $task)) {
            throw new UserNotAuthorisedException("Vous n'avez pas les authorisations pour supprimer cette tâche.");
        }
        return $task->delete();
    }

    /**
     * Update the sequence task_number_seq to use the highest available number
     */
    private function updateTaskNumberSequence()
    {
        if (config('database.default') !== 'sqlite') {
            \DB::statement("SELECT setval('task_number_seq', (SELECT MAX(number) FROM tasks))");
        }
    }

    public function saveOrUpdate(Task $task)
    {
        try {
            $this->save($task);
        } catch (\Exception $e) {
            $task->update();
        }
    }

    /**
     * @param Project $project
     */
    public function getTasksForDatatables(Project $project)
    {
        return $project->tasks()
            ->leftJoin('resource_types', 'tasks.resource_type_id', '=', 'resource_types.id')
            ->leftJoin('milestones', 'tasks.milestone_id', '=', 'milestones.id')
            ->select([
                'tasks.*',
                'tasks.id as DT_RowId',
                DB::raw(
                    "CASE WHEN tasks.active=true
                        THEN 'true'
                        ELSE 'false'
                    END as is_active"
                ),
                'resource_types.name as resource_type',
                'milestones.name as milestone'
            ])
            ->get();
    }

    /**
     * @param Project $project
     * @param array $ids
     * @return mixed
     */
    public function findByProjectInList(Project $project, $ids = array())
    {
        if (empty($ids)) {
            return new Collection();
        }
        return $project->tasks()->whereIn('id', $ids)->get();
    }

    /**
     * @param int $taskNumber
     * @return Task
     */
    public function findOneByNumberOrFail($taskNumber)
    {
        return Task::whereNumber($taskNumber)->firstOrFail();
    }

    /**
     * @param int $taskNumber
     * @return Task
     */
    public function findOneByNumber($taskNumber)
    {
        return Task::whereNumber($taskNumber)->first();
    }

    public function search(User $user, $term, $limit = 20)
    {
        $query['size'] = 500;

        $fields = ['project_client_name^2', 'project_name^3', 'project_number^4', 'task_number^5', 'task_name', 'task_name.folded'];

        $query['body']['query']['multi_match'] = [
            'fields'   => $fields,
            'query'    => $term,
            'type'     => 'most_fields',
            'operator' => 'and',
        ];

        if (!is_numeric($term)) {
            $query['body']['query']['multi_match']['fuzziness'] = 'AUTO';
        }

        $hits = SearchIndex::getResults($query)['hits'];

        return $this->getTasksForResults($user, $hits['hits'], $limit, is_numeric($term));
    }

    protected function getTasksForResults(User $user, $results, $limit, $onlyOne)
    {
        $taskIDs = [];
        foreach ($results as $hit) {
            $hitId = $hit['_id'];
            switch ($hit['_type']) {
                case 'task':
                    $taskIDs[] = $hitId;
                    break;
                case 'project':
                    $projectTasksIDs = Task::where('project_id', $hitId)->lists('id')->all();
                    $taskIDs += $projectTasksIDs;
                    break;
                case 'client':
                    $clientTasksIDs = DB::table('tasks')
                        ->select('tasks.id')
                        ->join('projects', 'projects.id', '=', 'tasks.project_id')
                        ->where('client_id', $hitId)
                        ->lists('tasks.id');
                    $taskIDs += $clientTasksIDs;
                    break;
                default:
                    continue;
                    break;
            }
        }

        $taskIdstr = implode(',', $taskIDs);

        //http://stackoverflow.com/a/1563636
        /** @var Builder $tasksQuery */
        $tasksQuery = DB::table('tasks')
            ->select('tasks.*')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->where('projects.active', '=', true)
            ->whereIn('tasks.id', $taskIDs)
            ->where('tasks.active', true)
            ->orderByRaw("active desc, position(tasks.id::text in '$taskIdstr')");

        if (!$user->canClockAnyProject()) {
            if ($onlyOne) {
                $tasksQuery->selectSub(
                    function (Builder $query) use ($user) {
                        $query
                        ->selectRaw('COUNT(*) AS can_clock')
                        ->from(DB::raw('tasks AS t'))
                        ->join('projects', 'projects.id', '=', 't.project_id')
                        ->join('user_project_roles', 'user_project_roles.project_id', '=', 't.project_id')
                        ->where('user_id', '=', $user->id)
                        ->whereRaw('t.id = tasks.id');
                    },
                    'can_clock'
                );
            } else {
                $tasksQuery
                    ->join('user_project_roles', 'user_project_roles.project_id', '=', 'tasks.project_id')
                    ->where('user_id', '=', $user->id);
            }
        }

        $tasksData = $tasksQuery->paginate($limit)->toArray();

        return Task::hydrate($tasksData['data']);
    }
}
