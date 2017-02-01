<?php

namespace NeoClocking\Http\Controllers\Api;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use NeoClocking\Exceptions\ModelOperationDeniedException;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Http\Requests\AskAccessRequest;
use NeoClocking\Models\UserRole;
use NeoClocking\Http\Requests\UserDeletesTaskRequest;
use NeoClocking\Http\Requests\UserUpdatesTaskRequest;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Repositories\UserRoleRepository;
use NeoClocking\Transformers\TaskTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TaskController
 */
class TaskController extends Controller
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var UserRoleRepository
     */
    protected $roleRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        TaskRepository $taskRepository,
        UserRoleRepository $roleRepository,
        UserRepository $userRepository
    ) {
        $this->taskRepository = $taskRepository;
        $this->roleRepository = $roleRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Return tasks for a term
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throw BadRequestHttpException
     */
    public function index(Request $request)
    {
        if (!$request->has('term')) {
            throw new BadRequestHttpException('The parameter `term` is required.');
        }

        $tasks = $this->taskRepository->search(user(), $request->get('term'));
        return fractal()->collection($tasks, TaskTransformer::class, 'tasks', ['project', 'client']);
    }

    /**
     * Display a task
     *
     * @param int $number
     * @return \Illuminate\Http\Response
     */
    public function show($number)
    {
        try {
            $this->ensureNumberIsValid($number);
            $task = $this->taskRepository->findOneByNumberOrFail($number);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException("La tâche #{$number} n'existe pas.");
        }

        if (user()->cannot('view', $task)) {
            throw new AccessDeniedHttpException("Vous n'avez pas accès à la tâche #{$number}");
        }

        return fractal()->item($task, TaskTransformer::class, 'tasks', ['project']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserUpdatesTaskRequest $request
     * @param int $number
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdatesTaskRequest $request, $number)
    {
        try {
            $task = Task::where(['number' => $number])->first();
            $task->fill($request->all());
            $this->taskRepository->save($task);

            return fractal()->item($task, TaskTransformer::class, 'tasks', ['project']);
        } catch (UserNotAuthorisedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (\Exception $e) {
            throw new BadRequestHttpException();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param UserDeletesTaskRequest $request
     * @param int $number
     */
    public function destroy(UserDeletesTaskRequest $request, $number)
    {
        $this->ensureNumberIsValid($number);
        try {
            $task = $this->taskRepository->findOneByNumberOrFail($number);
            $this->taskRepository->delete($task);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (UserNotAuthorisedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (ModelOperationDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

    }

    public function askAccess(AskAccessRequest $request, Mailer $mailer, $number)
    {
        $task = $this->taskRepository->findOneByNumberOrFail($number);
        $role = $this->roleRepository->findOneByCode(UserRole::CODE_MANAGER);
        $managers = $this->userRepository->getUserWithRoleInProject($role, $task->project);
        $reason = $request->get('reason');
        $user = $request->user();
        $project = $task->project;
        $url = env('LDAP_WEB_HOST') . '/projects/' . $project->number . '/edit';

        $mailer->send(
            'emails.request-access',
            compact('reason', 'user', 'project', 'task', 'url'),
            function (Message $msg) use ($managers, $project, $user) {
                $msg->subject('Demande d\'accès au projet ' . $project->number)
                    ->from('no-reply@libeo.com', 'Neoclocking')
                    ->replyTo($user->mail, $user->fullName)
                    ->to($managers->lists('fullName', 'mail')->toArray());
            }
        );
    }

    protected function ensureNumberIsValid($number)
    {
        // Field limited to 9 number. Postgres throw an error if the number is too high
        if (!preg_match('#^\d{1,9}$#', $number)) {
            throw new BadRequestHttpException('Le numéro de tâche n\'est pas valide.');
        }
    }
}
