<?php

namespace NeoClocking\Http\Controllers\Api;

use Illuminate\Http\Request;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Models\User;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Services\AuthenticationService;
use NeoClocking\Transformers\TaskTransformer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CurrentUserController
 */
class FavouriteTaskController extends Controller
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var User
     */
    protected $currentUser;

    /**
     * Inject dependancies
     *
     * @param AuthenticationService $authService
     * @param TaskRepository $taskRepository
     */
    public function __construct(AuthenticationService $authService, TaskRepository $taskRepository)
    {
        $this->currentUser = $authService->currentUser();
        $this->taskRepository = $taskRepository;
    }

    /**
     * List the favourite tasks of the current user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return fractal()->collection(
            $this->currentUser->favouriteTasks,
            TaskTransformer::class,
            'tasks',
            ['project', 'client']
        );
    }

    /**
     * Add task to favourite.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $task = $this->getTask($request);
        $this->currentUser->addFavouriteTask($task);
    }

    /**
     * Delete a task from the favourite.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $task = $this->getTask($request);
        if ($task) {
            $this->currentUser->removeFavouriteTasks($task);
        }
    }

    /**
     * @param Request $request
     * @return Task
     */
    protected function getTask(Request $request)
    {
        $number = $this->getTaskNumber($request);

        $task = $this->taskRepository->findOneByNumber($number);
        if (!$task) {
            throw new NotFoundHttpException("The task #{$number} do not exists.");
        }
        return $task;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function getTaskNumber(Request $request)
    {
        if (!$number = $request->get('number')) {
            throw new BadRequestHttpException('The `number` parameter is required.');
        }

        $this->ensureNumberIsValid($number);

        return $number;
    }

    /**
     * Ensure an id is valid
     *
     * @param $number
     * @throw NotFoundHttpException
     */
    protected function ensureNumberIsValid($number)
    {
        // Field limited to 9 number. Postgres throw an error if the number is too high
        if (!preg_match('#^\d{1,9}$#', $number)) {
            throw new NotFoundHttpException("The task {$number} do not exists.");
        }
    }
}
