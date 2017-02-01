<?php

namespace NeoClocking\Http\Requests;

use Dingo\Api\Http\FormRequest;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserUpdatesTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        $number = $this->route()->parameter('task_number');

        $this->ensureNumberIsValid($number);
        $task = Task::whereNumber($number)->first();

        $project = Project::whereId($this->json('project_id'))->first();

        $canUpdateTask = user()->can('update', $task);
        $canCreateTask = user()->can('manage', $project);

        return (
            $canUpdateTask ||
            (
                $project &&
                $project->id !== $task->project_id &&
                $canUpdateTask &&
                $canCreateTask
            )
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'               => 'sometimes|required',
            'active'             => 'sometimes|boolean',
            'estimation'         => 'sometimes|required|numeric',
            'revised_estimation' => 'sometimes|numeric',
            'resource_type_id'   => 'sometimes|exists:resource_types,id',
            'reference_type_id'  => 'sometimes|exists:reference_types,id',
            'reference_number'   => 'sometimes|numeric',
            'require_comments'   => 'sometimes|boolean',
            'project_id'         => 'sometimes|exists:projects,id',
        ];
    }


    protected function ensureNumberIsValid($number)
    {
        // Field limited to 9 number. Postgres throw an error if the number is too high
        if (!preg_match('#^\d{1,9}$#', $number)) {
            throw new BadRequestHttpException('Le numéro de tâche n\'est pas valide.');
        }
    }
}
