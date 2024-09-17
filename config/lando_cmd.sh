#!/usr/bin/env bash

if [ ! -d /app/web/sites/default/files ]; then
  echo -n "Creating PUBLIC directory ... "
  mkdir -p /app/web/sites/default/files
  echo "OK"
fi

if [ ! -d /app/web/sites/default/private ]; then
  echo -n "Creating PRIVATE directory ... "
  mkdir -p /app/web/sites/default/private
  echo "OK"
fi

if [[ ! -f /app/web/sites/default/private/.htaccess ]]; then
  cp /app/vendor/mrm-remora/aws-code-deploy/assets/aws/default/default.htaccess /app/web/sites/default/private/.htaccess
  chmod 644 /app/web/sites/default/private/.htaccess
fi