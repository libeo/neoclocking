<?php

namespace NeoClocking\Utilities;

use Carbon\Carbon;

class LogEntryUpdateDatetimeWindow
{
    public static function isOutside($datetimeToValidate, $now = 'now')
    {
        //$now = Carbon::parse($now)->firstOfMonth()->setTime(0, 0, 0)->subMonth();
        $datetimeToValidate = Carbon::parse($datetimeToValidate);
        $shouldBeThisMonthOrLater = Carbon::parse($now) > self::getDatetimeWindow($now);
        $shouldBePreviousMonthOrLater = Carbon::parse($now) <= self::getDatetimeWindow($now);

        $thisMonth = Carbon::parse($now)->firstOfMonth()->setTime(0, 0, 0);
        $previousMonth = Carbon::parse($now)->firstOfMonth()->setTime(0, 0, 0)->subMonth();

        if (($shouldBeThisMonthOrLater && $datetimeToValidate < $thisMonth)
            || ($shouldBePreviousMonthOrLater && $datetimeToValidate < $previousMonth)
        ) {
            return true;
        }
        return false;
    }

    public static function getDatetimeWindow($now = 'now')
    {
        return Carbon::parse($now)->firstOfMonth()->setTime(19, 0, 0);
    }
}
