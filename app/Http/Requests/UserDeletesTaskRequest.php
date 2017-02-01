<?php

namespace NeoClocking\Http\Requests;

use Dingo\Api\Http\FormRequest;
use NeoClocking\Models\Task;
use NeoClocking\Services\AuthenticationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserDeletesTaskRequest extends FormRequest
{
    /**
     * @var Task
     */
    protected $task;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @param AuthenticationService $authService
     * @return bool
     */
    public function authorize(AuthenticationService $authService)
    {
        $number = $this->route()->parameter('task_number');
        $this->ensureNumberIsValid($number);
        $this->task = Task::whereNumber($number)->firstOrFail();

        return $authService->currentUser()->can('delete', $this->task);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;

    }

    protected function ensureNumberIsValid($number)
    {
        // Field limited to 9 number. Postgres throw an error if the number is too high
        if (!preg_match('#^\d{1,9}$#', $number)) {
            throw new BadRequestHttpException('Le numéro de tâche n\'est pas valide.');
        }
    }
}
