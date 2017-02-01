<?php

namespace NeoClocking\Http\Requests;

use NeoClocking\Models\LogEntry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateLogEntryRequest extends LogEntryRequest
{
    /**
     * @var LogEntry
     */
    protected $logEntry;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return user()->can('update', $this->getLogEntry());
    }

    public function getLogEntry()
    {
        if (!isset($this->logEntry)) {
            $logId = $this->route()->parameter('log_entries');
            $this->ensureIdIsValid($logId);
            $this->logEntry = LogEntry::whereId($logId)->first();
        }

        if (!$this->logEntry) {
            throw new NotFoundHttpException();
        }
        return $this->logEntry;
    }

    public function getTaskId()
    {
        return $this->getLogEntry()->task_id;
    }
}
