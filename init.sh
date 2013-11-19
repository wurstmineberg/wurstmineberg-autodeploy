#!/bin/sh
# this script must be run as root

cd $(dirname $0)
touch www/statistics.json
chmod +rwx www/statistics.json

# configure user to the user under which the deploy system runs
# this will also be the user that owns all the git repositories
# the user should have access rights for the directory
# given in config.json::cloneLocation
user=updater

echo "[program:wurstmineberg-auto-deploy)]\n"\
     "command=$(pwd)/deploy.php\n"\
     "autostart=true\n"\
     "autorestart=unexpected\n"\
     "user=$user\n"\
     "directory=$(pwd)/\n" \
| sed -e 's/^ *//g' -e 's/ *$//g' \
> autodeploy_supervisord.conf
