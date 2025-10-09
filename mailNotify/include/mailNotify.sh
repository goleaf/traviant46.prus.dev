#!/bin/bash
users_list[0]="username";
SCRIPT_PATH=`dirname "$0"`;
SCRIPT_PATH=`eval "cd \"$SCRIPT_PATH\" && pwd"`
ROOT_DIR=`realpath "$SCRIPT_PATH/../.."`
set -e
function runNotificationCheck(){
    for i in "${users_list[@]}"
    do
        nohup /usr/bin/php > /dev/null 2>&1 "$ROOT_DIR/mailNotify/notification.php" "$i" &
    done
}
function runMailCheck(){
    for i in "${users_list[@]}"
    do
        nohup /usr/bin/php > /dev/null 2>&1 "$ROOT_DIR/mailNotify/mailman.php" "$i" &
    done
}
chmod +x "$ROOT_DIR/mailNotify/notification.php"
chmod +x "$ROOT_DIR/mailNotify/mailman.php"
last_notification_reload=0;
while [ true ]
do
    current_time=$(date +"%s");
    if [ $(($current_time-$last_notification_reload)) -gt 10 ]; then
        runNotificationCheck || true
        last_notification_reload=$current_time;
    fi
    runMailCheck || true
    sleep 5
done
