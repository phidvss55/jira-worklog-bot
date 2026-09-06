<?php

namespace App\Application\Worklog;

interface WorklogNotifier
{
    public function notify(LogWorkCommand $command): bool;
}
