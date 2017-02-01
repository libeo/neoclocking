<?php
namespace NeoClocking\Validators\Rules;

use NeoClocking\Utilities\LogEntryUpdateDatetimeWindow;

class AllowDateForUserValidator
{

    public function validate($attribute, $value, $parameters, $validator)
    {
        return !LogEntryUpdateDatetimeWindow::isOutside($value);
    }
}
