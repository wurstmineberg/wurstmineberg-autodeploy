#!/bin/sh

# configure user to the user under which the deploy system runs
# this will also be the user that owns all the git repositories
# the user should have access rights for the directory
# given in config.json::cloneLocation
user=updater

echo "[program:wurstmineberg-auto-deploy)]\n"\
     "command=$(pwd)/deploy.php\n"\
     "autostart=true\n"\
     "autorestart=true\n"\
     "user=$user\n"\
     "directory=$(pwd)/deploy.php\n" \
| sed -e 's/^ *//g' -e 's/ *$//g' \
> supervisord.conf
