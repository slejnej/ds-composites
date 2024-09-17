#!/usr/bin/env bash

APP='ds-composites'

# check if override exists then run it
SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
SCRIPT_NAME=$(basename "$0")
OVERRIDE="$(realpath -s "$SCRIPT_DIR/../../../../../../aws/hooks")"

if [ -f "$OVERRIDE/$SCRIPT_NAME" ]; then
  /bin/bash "$OVERRIDE/$SCRIPT_NAME"
  exit 0
fi

echo -e "\033[36m CodeDeploy \033[0m \033[32m${APP}\033[0m - START"

if [ ! -d /home/ubuntu/apps/$APP/releases ]; then
    echo -n "Creating releases folder ... "
    mkdir -p /home/ubuntu/apps/$APP/releases
    echo "OK"
fi

echo -n "Purging previous releases, except last 1 ..."
cd /home/ubuntu/apps/$APP/releases/ && \
ls -t | tail -n +2 | xargs rm -rf --
echo "OK"
