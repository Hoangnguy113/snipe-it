#!/bin/bash
mkdir -p /var/cache/glpi /var/lib/glpi_sessions
chmod -R 777 /var/cache/glpi /var/lib/glpi_sessions
chown -R www-data:www-data /var/cache/glpi /var/lib/glpi_sessions
service redis-server start
service mariadb start
service apache2 start
echo "GLPI Server is running on port 8080..."
echo "Redis status:"
service redis-server status | grep Active
echo "MariaDB status:"
service mariadb status | grep Active
echo "Apache status:"
service apache2 status | grep Active
# Keep container/process alive
while true; do
    if ! service apache2 status > /dev/null 2>&1; then
        service apache2 start
    fi
    if ! service mariadb status > /dev/null 2>&1; then
        service mariadb start
    fi
    sleep 5
done
