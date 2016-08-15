# cron-control, a PHP cron job manager

Manage all your cron jobs without modifying crontab. Handles locking, logging, error emails, and more.

## Features

- Maintain one master crontab job.
- Jobs run via PHP, so you can run them under any programmatic conditions.
- Use ordinary crontab schedule syntax (powered by the excellent [`cron-expression`](<https://github.com/mtdowling/cron-expression>)).
- Run only one copy of a job at a given time (by default).
- Send email whenever a job exits with an error status. 
- Run job as another user, if crontab user has `sudo` privileges.
- Run only on certain hostnames (handy in webfarms).
- Run by crontab file, there can be define logging, error email options
- Find crontab files by regular expression, parse and run jobs

## Credits

Developed before by [jobby](<https://github.com/jobbyphp/jobby>).
