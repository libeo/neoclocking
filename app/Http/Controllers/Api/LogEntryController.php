<?php

namespace NeoClocking\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use NeoClocking\Exceptions\ModelOperationDeniedException;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Http\Requests\StoreLogEntryRequest;
use NeoClocking\Http\Requests\UpdateLogEntryRequest;
use NeoClocking\Models\LogEntry;
use NeoClocking\Repositories\LogEntryRepository;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Transformers\LogEntryTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Class LogEntryController
 */
class LogEntryController extends Controller
{
    /**
     * @var LogEntryRepository
     */
    protected $logEntryRepository;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * LogEntryController constructor.
     *
     * @param LogEntryRepository $logEntryRepository
     * @param TaskRepository $taskRepository
     */
    public function __construct(LogEntryRepository $logEntryRepository, TaskRepository $taskRepository)
    {
        $this->logEntryRepository = $logEntryRepository;
        $this->taskRepository = $taskRepository;
    }

    /**
     * List log entries of the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($filter = $request->get('filterBy')) {
            $date = $request->get('date');

            try {
                $logEntries = $this->logEntryRepository->findByUserFiltered(user(), $filter, $date);
            } catch (InvalidParameterException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        } else {
            $logEntries = $this->logEntryRepository->findByUser(user());
        }

        $results = fractal()->collection(
            $logEntries->load('task', 'task.project', 'task.project.client', 'task.resourceType', 'task.referenceType'),
            LogEntryTransformer::class,
            'log-entries',
            ['task', 'project', 'client']
        );

        return $results;
    }

    /**
     * Show details of the entry for the given id.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $this->ensureIdIsValid($id);

        $logEntry = $this->logEntryRepository->findById($id);

        if (! $logEntry) {
            throw new NotFoundHttpException;
        }

        return fractal()->item($logEntry, LogEntryTransformer::class, 'log-entries', ['task', 'project', 'client']);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreLogEntryRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLogEntryRequest $request)
    {
        $this->ensureCanAddTime($request, $request->get('task_id'));

        $logEntryData = $request->only(['task_id', 'started_at', 'ended_at', 'comment']);
        $logEntry = $this->logEntryRepository->add(user(), $logEntryData);

        return fractal()->item(
            $logEntry,
            LogEntryTransformer::class,
            'log-entries',
            ['task', 'project', 'client']
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateLogEntryRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLogEntryRequest $request, $id)
    {
        try {
            $logEntry = LogEntry::findOrFail($id);

            $this->ensureCanAddTime($request, $logEntry->task_id, $logEntry);

            $logEntry->fill($request->all());
            $logEntry->save();

            return fractal()->item($logEntry, LogEntryTransformer::class, 'log-entries', ['task']);
        } catch (ModelNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $logEntry = LogEntry::whereId($id)->first();

        if (!$logEntry) {
            throw new NotFoundHttpException();
        }

        if (!user()->can('delete', $logEntry)) {
            throw new AccessDeniedHttpException();
        }

        try {
            LogEntry::destroy($id);
        } catch (ModelOperationDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }
    }

    /**
     * Ensure an id is valid
     *
     * @param $id
     * @throw NotFoundHttpException
     */
    protected function ensureIdIsValid($id)
    {
        // Field limited to 9 number. Postgres throw an error if the number is too high
        if (!preg_match('#^\d{1,9}$#', $id)) {
            throw new NotFoundHttpException();
        }
    }

    protected function ensureCanAddTime($request, $taskId, $originalLog = null)
    {
        $taskChanged = false;

        if (! empty($request->get('task_id')) && $request->get('task_id') !== $taskId) {
            $taskId = $request->get('task_id');
            $taskChanged = true;
        }

        $task = $this->taskRepository->findById($taskId);
        $project = $task->project;

        if (! $task->active) {
            throw new AccessDeniedHttpException('Cette tâche est fermée');
        }

        if (! $project->active) {
            throw new AccessDeniedHttpException('Ce projet est fermée');
        }

        if ($project->should_not_exceed) {
            $remainingTime = $project->max_time - $project->present()->calculateLoggedTimeTotal();

            $logEntry = new LogEntry($request->only(['task_id', 'started_at', 'ended_at', 'comment']));

            if (($originalLog === null && $remainingTime < $logEntry->time)
                || ($originalLog !== null
                    && $originalLog->time < $logEntry->time
                    && $remainingTime < $logEntry->time - $originalLog->time)
                || ($originalLog !== null && $taskChanged && $remainingTime < $logEntry->time)
            ) {
                $message = 'Il ne reste plus de temps dans cette banque d\'heures.';

                if ($remainingTime > 0) {
                    $time = $project->present()->remainingTime();
                    $message = 'Il ne reste que ' . $time . ' à la banque de temps.';
                }

                throw new AccessDeniedHttpException($message);
            }
        }

        return true;
    }
}
