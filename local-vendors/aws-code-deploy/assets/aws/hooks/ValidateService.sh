#!/usr/bin/env bash

# check if override exists then run it
SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
SCRIPT_NAME=$(basename "$0")
OVERRIDE="$(realpath -s "$SCRIPT_DIR/../../../../../../aws/hooks")"

if [ -f "$OVERRIDE/$SCRIPT_NAME" ]; then
  /bin/bash "$OVERRIDE/$SCRIPT_NAME"
  exit 0
fi


set -e

# restart what's needed
echo -ne "\033[36m Restarting \033[0m \033[32m Nginx \033[0m and \033[32m PHP-fpm ... \033[0m"

service nginx reload
# check what version is server running and update!
service php8.1-fpm restart
echo -e "\033[36mOK \033[0m"

# general server online check
echo -ne "\033[36m Performing health-check ...  \033[0m"
curl -sf http://localhost/health-check && echo -e "\033[32m OK \033[0m" || echo -e "\033[31m Failed \033[0m"
