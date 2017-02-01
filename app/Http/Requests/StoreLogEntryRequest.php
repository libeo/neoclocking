<?php

namespace NeoClocking\Http\Requests;

class StoreLogEntryRequest extends LogEntryRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $task = $this->getTask();
        return user()->can('workOn', $task);
    }

    protected function getTaskId()
    {
        return $this->get('task_id');
    }
}
