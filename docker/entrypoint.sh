#!/bin/sh
# Starts php-fpm (backgrounded) and nginx (foreground) in this single container.
set -e

PORT="${PORT:-8080}"

mkdir -p /tmp/nginx/client_body /tmp/nginx/proxy /tmp/nginx/fastcgi \
         /tmp/nginx/uwsgi /tmp/nginx/scgi

# nginx cannot read environment variables, so the port is substituted in here.
sed "s/__PORT__/${PORT}/g" /etc/nginx/nginx.conf.template > /tmp/nginx.conf

php-fpm -y /usr/local/etc/php-fpm.d/zz-app.conf -D

exec nginx -p /etc/nginx/ -c /tmp/nginx.conf -e /dev/stderr -g "daemon off;"
