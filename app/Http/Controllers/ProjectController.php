<?php

namespace NeoClocking\Http\Controllers;

use Illuminate\Foundation\Application;
use App;
use DB;
use Input;
use NeoClocking\Jobs\LibeoDapProjectSync;
use Validator;
use Datatables;
use Lang;
use SearchIndex;
use NeoClocking\Models\Project;
use NeoClocking\Models\ResourceType;
use NeoClocking\Models\User;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Services\AuthenticationService;

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
     * @param ProjectRepository $projectRepository
     * @param ResourceTypeRepository $resourceTypeRepository
     * @param Application $app
     */
    public function __construct(
        AuthenticationService $authService,
        ProjectRepository $projectRepository,
        ResourceTypeRepository $resourceTypeRepository,
        Application $app
    ) {
        $this->currentUser = $user = $authService->currentUser();
        $this->projectRepository = $projectRepository;
        $this->resourceTypeRepository = $resourceTypeRepository;
        $this->app = $app;
    }


    /**
     * Display list of projects of the user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = $this->currentUser;

        return view('project.index')->with(compact('user'));
    }

    /**
     * Display a project and its tasks
     *
     * @param string $projectNumber
     * @return \Illuminate\View\View
     */
    public function show($projectNumber)
    {
        $user = $this->currentUser;
        $project = $this->projectRepository->findOneByNumberOrFail($projectNumber);

        $this->ensureCurrentUserCanViewProject($project);

        $resourceTypes = $this->resourceTypeRepository->findAllForSelect();
        $milestones = [];

        if ($user->can('manage', $project)) {
            $milestones = $project->milestones()->orderBy('name')->lists('name', 'id');
        }

        return view('project.show', compact('project', 'user', 'resourceTypes', 'milestones'));
    }

    /**
     * Ensure the current user can view a project
     *
     * @param Project $project
     */
    protected function ensureCurrentUserCanViewProject(Project $project)
    {
        if (! $this->currentUser->can('view', $project)) {
            $this->app->abort(403);
        }
    }
}
