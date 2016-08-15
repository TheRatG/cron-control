<?php
return [
    'a7a710b55299b4c297b7919e6ff08200' =>
        [
            'recipients' => 'dqesupport@gmail.com,cron@test2.com',
            'schedule' => '* * * * *',
            'output' => null,
            'command' => 'cd /web/statistics_stable/sites/stable.statistics.roboforex.com/htdocs/; ./cron.php /gearman/cron/restoreWorkers/',
        ],
];