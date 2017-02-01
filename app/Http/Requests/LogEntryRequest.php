<?php

namespace NeoClocking\Http\Requests;

use Dingo\Api\Http\FormRequest;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\TaskRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class LogEntryRequest extends FormRequest
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var Task
     */
    protected $task;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'task_id'    => 'required|regex:#^\d{1,9}$#|exists:tasks,id',
            'started_at' => 'required|date|'.$this->getAllowDateForUser(),
            'ended_at'   => 'required|date|after:started_at',
            'comment'    => $this->isCommentRequired()
        ];
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'task_id.regex'=>'Le champ task id sélectionné est invalide.'
        ];
    }

    protected function isCommentRequired()
    {
        return $this->getTask()->require_comments ? 'required' : '';
    }

    protected function getAllowDateForUser()
    {
        $user = user();

        if ($user && !$user->canClockOutsideTimeWindow()) {
            return 'allowDateForUser';
        }

        return '';
    }

    protected function getTask()
    {
        if (!isset($this->task)) {
            /** @var TaskRepository $taskRepository */
            $taskRepository = $this->container->make(TaskRepository::class);
            $taskId = $this->getTaskId();

            $this->ensureIdIsValid($taskId);

            $task = $taskRepository->findById($taskId);

            if (!$task) {
                throw new NotFoundHttpException("The task #{$taskId} do not exists.");
            }
            $this->task = $task;
        }
        return $this->task;
    }

    abstract protected function getTaskId();

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
}
