<?php
namespace NeoClocking\Presenters;

use Caffeinated\Presenter\Presenter;

class BasePresenter extends Presenter
{
    /**
     * Format minutes as Hours:Minutes
     * ex. 63 becomes 1:03
     *
     * @param int $totalMinutes
     * @return string
     */
    protected function minutesToHours($totalMinutes)
    {
        $negative = ($totalMinutes < 0);
        $totalMinutes = abs($totalMinutes);
        $hours = floor($totalMinutes / 60);
        if ($negative) {
            $hours = "-$hours";
        }
        $minutes = str_pad($totalMinutes % 60, 2, '0', STR_PAD_LEFT);

        return "$hours:$minutes";
    }
}
