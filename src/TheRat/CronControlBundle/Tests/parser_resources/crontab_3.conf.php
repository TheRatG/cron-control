<?php
return [
    '3411604fdfe7f1fcde21293efc79864d' =>
        [
            'schedule' => '* * * * *',
            'output' => '/tmp/hello',
            'command' => 'export myvar="hi man"; echo "$myvar. date is $(date)"',
        ],
];