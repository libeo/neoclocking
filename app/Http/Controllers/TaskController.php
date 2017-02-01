<?php

namespace NeoClocking\Http\Controllers;

use Illuminate\Foundation\Application;
use NeoClocking\Exceptions\ModelOperationDeniedException;
use NeoClocking\Http\Requests\UpdateTaskRequest;
use NeoClocking\Models\ReferenceType;
use NeoClocking\Models\ResourceType;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;
use NeoClocking\Repositories\LogEntryRepository;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Services\AuthenticationService;

/**
 * Class TaskController
 */
class TaskController extends Controller
{
    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var LogEntryRepository
     */
    protected $logEntryRepository;

    /**
     * @var ResourceTypeRepository
     */
    protected $resourceTypeRepository;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Inject dependencies
     *
     * @param AuthenticationService $authService
     * @param TaskRepository $taskRepository
     * @param LogEntryRepository $logEntryRepository
     * @param ResourceTypeRepository $resourceTypeRepository
     * @param Application $app
     */
    public function __construct(
        AuthenticationService $authService,
        TaskRepository $taskRepository,
        LogEntryRepository $logEntryRepository,
        ResourceTypeRepository $resourceTypeRepository,
        Application $app
    ) {
        $this->currentUser = $user = $authService->currentUser();
        $this->taskRepository = $taskRepository;
        $this->logEntryRepository = $logEntryRepository;
        $this->resourceTypeRepository = $resourceTypeRepository;
        $this->app = $app;
    }

    /**
     * Display a task
     *
     * @param int $taskNumber
     * @return \Illuminate\View\View
     */
    public function show($taskNumber)
    {
        $task = $this->taskRepository->findOneByNumberOrFail($taskNumber);

        $this->ensureCurrentUserCanViewTask($task);

        $user = $this->currentUser;
        $logEntries = $this->logEntryRepository->findByTaskPaginated($task);

        $viewProperties = compact('task', 'user', 'logEntries');

        if ($this->currentUser->can('update', $task)) {
            $resourceTypes = $this->resourceTypeRepository->findAllForSelect();
            $referenceTypes = ReferenceType::all()->lists('name', 'id');
            $milestones = $task->project->milestones()->orderBy('name', 'asc')->lists('name', 'id');

            $viewProperties += compact('resourceTypes', 'referenceTypes', 'milestones');
        }

        return view('task.show')
            ->with($viewProperties);
    }

    /**
     * Update a task
     *
     * @param UpdateTaskRequest $request
     * @return \Illuminate\View\View
     */
    public function update(UpdateTaskRequest $request)
    {
        $task = $request->getTask();
        try {
            $task->fill($request->all());
            $saved = $this->taskRepository->save($task);

            if ($saved) {
                $request->session()->flash('alert-success', "La tâche #{$task->number} a été mise à jour.");
            } else {
                $request->session()->flash('alert-danger', "La tâche #{$task->number} n'a pas pu être mise à jour.");
            }
        } catch (ModelOperationDeniedException $e) {
            $request->session()->flash('alert-danger', $e->getMessage());
        }

        return redirect()->route('task.show', ['taskNumber' => $task->number]);
    }

    /**
     * Ensure the current user can view a task
     *
     * @param Task $task
     */
    protected function ensureCurrentUserCanViewTask(Task $task)
    {
        if (! $this->currentUser->can('view', $task)) {
            $this->app->abort(403);
        }
    }
}
