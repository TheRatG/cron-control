<?php
return [
    'e4381b7b2581b6a745609e90aad7494b' =>
        [
            'recipients' => '132123131@testmail.com',
            'schedule' => '* * * * *',
            'output' => null,
            'command' => 'cd /web/www/sites/my_project/htdocs/; ./cron.php /cron/devel/logCrontabList/',
        ],
    '7cd96de131fe4cb5fbfeadf5d3ce5dae' =>
        [
            'recipients' => '132123131@testmail.com',
            'schedule' => '0 20 * * *',
            'output' => null,
            'command' => 'cd /web/www/sites/my_project/htdocs/; ./cron.php /cron/devel/disableCrontab/',
        ],
];
