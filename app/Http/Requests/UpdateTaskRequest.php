<?php

namespace NeoClocking\Http\Requests;

use NeoClocking\Models\Task;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Services\AuthenticationService;

class UpdateTaskRequest extends Request
{
    /**
     * @var Task
     */
    protected $task;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @param TaskRepository $taskRepository
     * @return bool
     */
    public function authorize(TaskRepository $taskRepository, AuthenticationService $authService)
    {
        $this->task = $taskRepository->findOneByNumberOrFail($this->route()->parameter('taskNumber'));

        return $authService->currentUser()->can('update', $this->task);
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'               => 'required',
            'active'             => 'sometimes|boolean',
            'milestone_id'       => 'exists:milestones,id',
            'estimation'         => ['required','regex:#^(\d+|\d+:\d{2})$#'],
            'revised_estimation' => ['regex:#^(\d+|\d+:\d{2})$#'],
            'resource_type_id'   => 'sometimes|required|exists:resource_types,id',
            'reference_type_id'  => 'exists:reference_types,id',
            'reference_number'   => 'numeric',
            'require_comments'   => 'boolean',
            'project_id'         => 'required|exists:projects,id',
        ];
    }
}
