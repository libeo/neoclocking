<?php

namespace NeoClocking\Utilities;

class TimeFormater
{
    /**
     * Convert hours:minutes string to minutes ex. "2:30" -> 90;
     *
     * @param string $formattedTime
     * @return int
     */
    public static function formattedTimeToMinutes($formattedTime)
    {
        if (empty($formattedTime)) {
            return 0;
        }

        $timeParts = explode(':', $formattedTime);
        if (count($timeParts) === 2) {
            $hours = (int) $timeParts[0];
            return ((int) $timeParts[1]) + ($hours * 60);
        }
        return $formattedTime;
    }
}
