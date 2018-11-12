#!/bin/sh
chown -R www-data:www-data /app
touch /logs/error.log
chown www-data:www-data /logs/error.log
mkdir -p /pagely-sidecar/
ID=$(basename "$(head /proc/1/cgroup)")
IP=$(ip -o -4 addr list eth0 | head -n1 | awk '{print $4}' | cut -d/ -f1)
cat << EOF > /pagely-sidecar/primary.json
{"IP":"${IP}","HOSTNAME":"${HOSTNAME}","ID":"${ID}","APP_ID":"${APP_ID}","ORG_ID":"${ORG_ID}"}
EOF
date '+%Y-%m-%d %H:%M:%S' > /app/.complete
exec "$@"
