<?php

namespace NeoClocking\Http\Controllers\Api;

use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use NeoClocking\Exceptions\ModelOperationDeniedException;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Models\Milestone;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Services\AuthenticationService;
use NeoClocking\Services\TaskService;
use NeoClocking\Transformers\MilestoneTransformer;
use NeoClocking\Transformers\ProjectTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yajra\Datatables\Datatables;

/**
 * Class ProjectController
 */
class ProjectController extends Controller
{
    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var ProjectRepository
     */
    protected $projectRepository;

    /**
     * ProjectController constructor.
     *
     * @param AuthenticationService $authService
     * @param ProjectRepository $projectRepository
     */
    public function __construct(AuthenticationService $authService, ProjectRepository $projectRepository)
    {
        $this->currentUser = $user = $authService->currentUser();
        $this->projectRepository = $projectRepository;
    }

    /**
     * Get all projects information.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('term')) {
            $term = $request->get('term');
            $projects = $this->projectRepository->search($this->currentUser, $term, 25);
        } else {
            $projects = $this->currentUser->projects()->active()->paginate(25);
        }

        return fractal()->collection($projects, ProjectTransformer::class, 'projects', ['client']);
    }

    /**
     * Get all the connected user projects.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $projects = $this->currentUser->projects()->active()->get();

        return fractal()->collection($projects, ProjectTransformer::class, 'projects', ['client']);
    }

    /**
     * Get the information of a project.
     *
     * @param string $projectNumber
     * @return \Illuminate\Http\Response
     */
    public function show($projectNumber)
    {
        $project = $this->getProject($projectNumber);

        return fractal()->item($project, ProjectTransformer::class, 'projects');
    }

    /**
     * Return tasks of a project, for Datatables
     *
     * @param TaskRepository $taskRepository
     * @param string $projectNumber
     * @return mixed
     */
    public function tasks(TaskRepository $taskRepository, $projectNumber)
    {
        $project = $this->getProject($projectNumber);

        $taskData = $taskRepository->getTasksForDatatables($project);

        return Datatables::of($taskData)->make(true);
    }

    /**
     * Update tasks
     *
     * @param string $projectNumber
     * @param Request $request
     * @param TaskService $taskService
     * @return \Illuminate\View\View
     */
    public function updateTasks(Request $request, TaskService $taskService, $projectNumber)
    {
        $project = $this->getManageableProject($projectNumber);

        $tasksData = $request->input('tasks');
        if (empty($tasksData)) {
            throw new BadRequestHttpException('The request need to contain `tasks`.');
        }

        try {
            $tasks = $taskService->formatTasks($project, $tasksData);
            $taskService->saveTasks($tasks);
        } catch (ModelValidationException $e) {
            throw new UpdateResourceFailedException('Some tasks could not be saved.', $e->getValidationErrors());
        } catch (ModelOperationDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }
    }

    /**
     * Delete tasks
     *
     * @param Request $request
     * @param ConnectionInterface $db
     * @param TaskRepository $taskRepository
     * @param string $projectNumber
     * @return array
     */
    public function deleteTasks(
        Request $request,
        ConnectionInterface $db,
        TaskRepository $taskRepository,
        $projectNumber
    ) {
        $project = $this->getManageableProject($projectNumber);

        $tasks = $taskRepository->findByProjectInList($project, $request->get('ids'));

        try {
            $db->transaction(function () use ($tasks) {
                /** @var Task $task */
                foreach ($tasks as $task) {
                    $task->delete();
                }
            });
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException();
        } catch (UserNotAuthorisedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (ModelOperationDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }
    }

    public function milestones($projectNumber)
    {
        $project = $this->getManageableProject($projectNumber);
        $milestones = Milestone::all();
        return fractal()->collection($milestones, MilestoneTransformer::class, 'milestones');
    }

    /**
     * Create a milestone and return the new list
     *
     * @param Request $request
     * @param string $projectNumber
     * @return array
     */
    public function createMilestone(Request $request, $projectNumber)
    {
        $project = $this->getManageableProject($projectNumber);

        $validator = Validator::make($request->all(), [
            'milestone_name' => 'required|unique:milestones,name,NULL,id,project_id,'.$project->id
        ], [
            'milestone_name.required' => 'The milestone name is required.',
            'milestone_name.unique' => 'The milestone name is already used.'
        ]);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('Could not save the milestone.', $validator->errors());
        }

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'name' => $request->get('milestone_name')
        ]);

        $milestones = $project->milestones()->orderBy('name')->lists('name', 'id');

        return ['id' => $milestone->id, 'milestones' => $milestones];
    }

    /**
     * @param string $projectNumber
     * @param bool $manageable
     * @return Project
     */
    protected function getProject($projectNumber, $manageable = false)
    {
        $project = Project::whereNumber($projectNumber)->first();

        if (!$project) {
            throw new NotFoundHttpException;
        }

        if ($manageable) {
            $this->ensureCurrentUserCanManageProject($project);
        } else {
            $this->ensureCurrentUserCanViewProject($project);
        }

        return $project;
    }

    /**
     * @param string $projectNumber
     * @return Project
     */
    protected function getManageableProject($projectNumber)
    {
        return $this->getProject($projectNumber, true);
    }

    /**
     * Ensure the current user can view a project
     *
     * @param Project $project
     */
    protected function ensureCurrentUserCanViewProject(Project $project)
    {
        if (! $this->currentUser->can('view', $project)) {
            throw new AccessDeniedHttpException("You don't have access to the project {$project->number}.");
        }
    }

    /**
     * Ensure the current user can manage a project
     *
     * @param Project $project
     */
    protected function ensureCurrentUserCanManageProject(Project $project)
    {
        if (! $this->currentUser->can('manage', $project)) {
            throw new AccessDeniedHttpException("You don't have the rights to manage the project {$project->number}.");
        }
    }
}
