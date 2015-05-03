#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

apt-get install -y mysql-server

MYSQL_ROOT_PASS='admin.asdwsx'
echo "mysql-server-5.6 mysql-server/root_password password $MYSQL_ROOT_PASS" | sudo debconf-set-selections
echo "mysql-server-5.6 mysql-server/root_password_again password $MYSQL_ROOT_PASS" | sudo debconf-set-selections

