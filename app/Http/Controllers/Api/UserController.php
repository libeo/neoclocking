<?php

namespace NeoClocking\Http\Controllers\Api;

use Illuminate\Http\Request;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Services\AuthenticationService;
use NeoClocking\Transformers\UserTransformer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UserController
 */
class UserController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var ProjectRepository
     */
    protected $projectRepository;

    public function __construct(UserRepository $users, ProjectRepository $projects)
    {
        $this->userRepository = $users;
        $this->projectRepository = $projects;
    }

    /**
     * List all users
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = $this->userRepository->findAll();

        return fractal()->collection($users, UserTransformer::class, 'users');
    }

    /**
     * Display the user associated with the given username
     *
     * @param string $username
     * @return \Illuminate\Http\Response
     */
    public function show($username)
    {
        $user = $this->getUserByUsername($username);

        return fractal()->item($user, UserTransformer::class, 'user');
    }

    /**
     * Display the user's Work-time data for the current moment
     *
     * @param Request $request
     * @param string $username
     * @return \int[]
     */
    public function workedTime(Request $request, $username)
    {
        $user = $this->getUserByUsername($username);

        $date = 'today';
        if ($request->has('date')) {
            $date = $request->get('date');
        }

        $data = [
            'duration_week'         => $user->week_duration,
            'duration_day'          => ($user->week_duration / 5),
            'time_worked_this_week' => $user->getService()->getTimeWorkedInWeek($date),
            'time_worked_today'     => $user->getService()->getTimeWorkedOnDate($date),
        ];
        return $data;
    }

    public function timePerDay($username)
    {
        $user = $this->getUserByUsername($username);

        $timePerDay = $user->week_duration / 5;

        return ['data' => ['time_per_day' => $timePerDay]];
    }

    public function timeRemainingThisWeek($username)
    {
        $user = $this->getUserByUsername($username);

        $timeRemaining = $user->getService()->getTimeRemainingInWeek();

        return ['data' => ['time_remaining' => $timeRemaining]];
    }

    /**
     * @param Request $request
     * @param AuthenticationService $authService
     * @return array
     */
    public function userAuth(Request $request, AuthenticationService $authService)
    {
        $data = [];

        try {
            $authService->login($request->only('username', 'password'));
            $user = $authService->currentUser();

            $data['api_key'] = $user->api_key;
        } catch (UserNotAuthorisedException $exception) {
            $data['status'] = 'unauthorised';
        }

        return $data;
    }

    /**
     * @param $username
     * @return \NeoClocking\Models\User
     */
    protected function getUserByUsername($username)
    {
        if ($username === 'me') {
            $user = user();
        } else {
            $user = $this->userRepository->findOneByUsername($username);

            if (!$user) {
                throw new NotFoundHttpException("Could not find user \"{$username}\".");
            }
        }

        return $user;
    }
}
