#!/bin/bash
set -e

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOSQL
CREATE DATABASE IF NOT EXISTS keycloak_db;
CREATE USER IF NOT EXISTS 'keycloak_user'@'%' IDENTIFIED BY '${KEYCLOAK_DB_PASSWORD}';
GRANT ALL PRIVILEGES ON keycloak_db.* TO 'keycloak_user'@'%';
EOSQL
