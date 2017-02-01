<?php

namespace NeoClocking\Http\Controllers\Api;

use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Models\LiveLogEntry;
use NeoClocking\Models\Task;
use NeoClocking\Services\AuthenticationService;
use NeoClocking\Transformers\LiveLogEntryTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LiveEntryController
 */
class LiveEntryController extends Controller
{
    /**
     * @var AuthenticationService
     */
    protected $currentUser;

    /**
     * Inject dependencies
     *
     * @param AuthenticationService $authService
     */
    public function __construct(AuthenticationService $authService)
    {
        $this->currentUser = $authService->currentUser();
    }

    /**
     * Return the current live clocking entry, if any
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $liveEntry = $this->getCurrentUserLiveEntry();

        return fractal()->item($liveEntry, LiveLogEntryTransformer::class, null, ['task.project.client']);
    }

    /**
     * Save a new live clocking entry
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throw ConflictHttpException
     */
    public function store(Request $request)
    {
        if ($this->getCurrentUserLiveEntry()) {
            throw new ConflictHttpException('A live clocking session is already running.');
        }

        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
            'started_at' => 'required|date',
        ], [
            'task_id.required' => 'The task is required.',
            'started_at.required' => 'The start time is required.',
            'started_at.date' => 'The start time is not valid.',
        ]);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('Could not save the live entry.', $validator->errors());
        }

        $taskId = $request->get('task_id');
        $task = Task::find($taskId);

        if (!$task) {
            throw new NotFoundHttpException("La tâche #{$taskId} n'existe pas.");
        }

        if (!$task->active) {
            throw new AccessDeniedHttpException("La tâche #{$taskId} est fermé.");
        }

        if (!$this->currentUser->can('view', $task)) {
            throw new AccessDeniedHttpException("Vous n'avez pas accès à la tâche #{$task->number}");
        }

        $liveEntry = new LiveLogEntry($request->all());
        $liveEntry->user_id = $this->currentUser->id;
        $liveEntry->save();

        return fractal()->item($liveEntry, LiveLogEntryTransformer::class, null, ['task.project.client']);
    }

    /**
     * Update the current live clocking entry
     *
     * @param Request $request
     * @throw GoneHttpException
     */
    public function update(Request $request)
    {
        $liveEntry = $this->getCurrentUserLiveEntry();

        if (!$liveEntry) {
            throw new GoneHttpException('No live clocking session running.');
        }

        $liveEntry->fill($request->only(['comment']));
        $liveEntry->save();
    }

    /**
     * Delete the current live clocking entry
     */
    public function destroy()
    {
        $liveEntry = $this->getCurrentUserLiveEntry();
        if ($liveEntry) {
            $liveEntry->delete();
        }
    }

    /**
     * Return the current user live entry
     *
     * @return LiveLogEntry|null
     */
    protected function getCurrentUserLiveEntry()
    {
        return LiveLogEntry::whereUserId($this->currentUser->id)->first();
    }
}
