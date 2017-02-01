<?php

namespace NeoClocking\Services;

use App;
use Carbon\Carbon;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\User;
use NeoClocking\Repositories\LogEntryRepository;

class UserService
{

    /**
     * @var User
     */
    protected $user;

    /**
     * @var LogEntryRepository
     */
    protected $logEntryRepository;

    public function __construct(User $user, LogEntryRepository $logEntryRepository)
    {
        $this->user = $user;
        $this->logEntryRepository = $logEntryRepository;
    }

    /**
     * @param string $weekReferenceDate day of week formatted for strtotime
     *
     * @return int Total minutes worked in week by the user
     */
    public function getTimeWorkedInWeek($weekReferenceDate = "today")
    {
        $logs = $this->logEntryRepository->getLogsForWeekOfDate($this->user, $weekReferenceDate);
        return $this->getTotalTimeFromLogs($logs);
    }

    /**
     * @param string $day day of week formatted for strtotime
     *
     * @return float|int Returns total of hours worked on date by this user
     */
    public function getTimeWorkedOnDate($day = "today")
    {
        $logs = $this->logEntryRepository->getLogsForDate($this->user, $day);
        return $this->getTotalTimeFromLogs($logs);
    }

    /**
     * @param LogEntry[] $logs
     * @return int minutes
     */
    public function getTotalTimeFromLogs($logs)
    {
        $total = 0;
        foreach ($logs as $log) {
            $end = $log->ended_at;
            if (! empty($end)) {
                $total += $end->diffInMinutes($log->started_at);
            }
        }

        return $total;
    }

    /**
     * Minutes the user would work per day
     * if said user worked equal hours each day
     */
    public function getDayDuration()
    {
        return floor($this->user->week_duration / 5);
    }

    /**
     * @param string $day
     *
     * @return \Illuminate\Database\Eloquent\Collection|LogEntry[]
     */
    public function getDateLogs($day)
    {
        $date = Carbon::parse($day, 'America/Montreal');
        $start = $date->copy()->setTime(0, 0, 0);
        $end = $date->copy()->setTime(23, 59, 59);

        /** @var LogEntryRepository $logEntryRepo */
        $logEntryRepo = app(LogEntryRepository::class);
        $logs = $logEntryRepo
            ->findByUserWithDateRange(
                $this->user,
                $start,
                $end
            );
        return $logs;
    }

    /**
     * @return int[]
     */
    public function getWorkedTime()
    {
        return [
            'duration_week'         => $this->user->week_duration,
            'duration_day'          => $this->getDayDuration(),
            'time_worked_this_week' => $this->getTimeWorkedInWeek(),
            'time_worked_today'     => $this->getTimeWorkedOnDate(),
        ];
    }

    /**
     * @param string $weekReferenceDate
     * @return int|float
     */
    public function getTimeRemainingInWeek($weekReferenceDate = 'today')
    {
        $weekDuration = $this->user->week_duration;
        $timeWorked = $this->getTimeWorkedInWeek($weekReferenceDate);

        return $weekDuration - $timeWorked;
    }
}
