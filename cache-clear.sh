#!/bin/bash
php app/console cache:clear
php app/console cache:clear --env=prod
#php app/console cache:warmup
chmod 777 app/cache -R
chmod 777 app/logs -R
