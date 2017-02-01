<?php

namespace NeoClocking\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;

class LogEntryRepository
{
    /**
     * @param integer $id
     *
     * @return LogEntry
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return LogEntry::whereId($id)->first();
    }

    /**
     * @param Task $task
     * @return \Illuminate\Database\Eloquent\Collection|LogEntry[]
     */
    public function findByTask(Task $task)
    {
        return LogEntry::whereTaskId($task->id)->paginate(25);
    }

    /**
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection|LogEntry[]
     */
    public function findByUser(User $user)
    {
        return LogEntry::whereUserId($user->id)->orderBy('started_at', 'desc')->paginate(25);
    }

    public function findByUserFiltered(User $user, $filterBy, $date)
    {
        if ($date) {
            $this->ensureDateIsParsable($date);
        }

        switch ($filterBy) {
            case 'day':
                $logEntries = $this->getLogsForDate($user, $date);
                break;
            case 'week':
                $logEntries = $this->getLogsForWeekOfDate($user, $date);
                break;
            default:
                throw new InvalidParameterException('The filterBy parameter must be `week` or `day`.');
        }
        return $logEntries;
    }

    /**
     * @param User $user
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \Illuminate\Database\Eloquent\Collection|LogEntry[]
     */
    public function findByUserWithDateRange(User $user, Carbon $start, Carbon $end)
    {
        /** @var Builder $query */
        $query = LogEntry::whereUserId($user->id);

        $startTime = $start->setTimezone('UTC')->toDateTimeString();
        $endTime = $end->setTimezone('UTC')->toDateTimeString();

        $query->whereBetween('started_at', [$startTime, $endTime]);

        return $query->orderBy('started_at')->get();
    }

    /**
     * @param LogEntry $log
     * @return bool
     * @throws UserNotAuthorisedException
     * @throws ModelValidationException
     */
    public function save(LogEntry $log)
    {
        if ($log->exists) {
            if (! user()->can('update', $log)) {
                throw new UserNotAuthorisedException(
                    "Vous n'avez pas les droits pour créer/modifier ce temps"
                );
            }
        }

        $task = $log->task;
        if (! user()->can('workOn', $task)) {
            throw new UserNotAuthorisedException(
                "Vous n'avez pas les droits pour travailler sur la tâche {$task->number}"
            );
        }

        return $log->save();
    }

    /**
     * @param LogEntry $log
     *
     * @return bool Success or failure of the delete
     *
     * @throws UserNotAuthorisedException
     */
    public function delete(LogEntry $log)
    {
        if (! user()->can('delete', $log)) {
            throw new UserNotAuthorisedException(
                "Vous n'avez pas le droit de supprimer ce temps"
            );
        }
        return $log->delete();
    }

    public function findByTaskPaginated(Task $task)
    {
        return $task->logEntries()->orderBy('started_at', 'desc')->with('user')->paginate(50);
    }

    public function saveOrUpdate(LogEntry $log)
    {
        try {
            $this->save($log);
        } catch (\Exception $e) {
            $log->update();
        }
    }

    /**
     * Ensure a date is parsable
     *
     * @param string $date
     * @throw BadRequestHttpException
     */
    protected function ensureDateIsParsable($date)
    {
        try {
            Carbon::parse($date);
        } catch (\Exception $e) {
            throw new InvalidParameterException('The `date` parameter is invalid.');
        }
    }

    public function getLogsForDate($user, $date)
    {
        $date = Carbon::parse($date, 'America/Montreal');
        $start = $date->copy()->setTime(0, 0, 0);
        $end = $date->copy()->setTime(23, 59, 59);

        $logs = $this
            ->findByUserWithDateRange(
                $user,
                $start,
                $end
            );
        return $logs;
    }

    public function getLogsForWeekOfDate($user, $date)
    {
        $referenceDate = Carbon::parse($date, 'America/Montreal');
        $dayInWeek = $referenceDate->dayOfWeek;

        $startOfWeek = $referenceDate;
        if ($dayInWeek != 0) {
            $startOfWeek = $referenceDate->copy()->previous(Carbon::SUNDAY);
        }
        $endOfWeek = $referenceDate->copy()->next(Carbon::SUNDAY)->subSecond();

        $logs = $this
            ->findByUserWithDateRange(
                $user,
                $startOfWeek,
                $endOfWeek
            );

        return $logs;
    }

    public function add(User $user, $data)
    {
        $logEntry = new LogEntry($data);

        $logEntry->user_id = $user->id;
        $logEntry->hourly_cost = $user->hourly_cost / 100;

        $logEntry->save();

        return $logEntry;
    }
}
