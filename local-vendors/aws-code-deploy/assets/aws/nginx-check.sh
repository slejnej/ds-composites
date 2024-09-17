#!/usr/bin/env bash

# check if nginx.conf has required entries

nginx_file="/etc/nginx/nginx.conf"
if ! grep -q real_ip_header "$nginx_file"; then
  sed -i '/^http {/a \\n\treal_ip_header X-Forwarded-For;\n\tset_real_ip_from 0.0.0.0/0;' "$nginx_file"
fi