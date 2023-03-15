<?php

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class SendReportEmail extends ScheduledTask
{
    public function executeActions()
    {
        return true;
    }
}
