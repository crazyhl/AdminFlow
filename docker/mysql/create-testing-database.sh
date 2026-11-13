#!/usr/bin/env bash

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS adminflow_testing;
    GRANT ALL PRIVILEGES ON adminflow_testing.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
EOSQL
