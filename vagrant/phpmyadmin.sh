#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
MYSQL_ROOT_PASS='admin.asdwsx'

if [ ! -f /etc/phpmyadmin/config.inc.php ];
then
	echo "phpmyadmin phpmyadmin/dbconfig-install boolean false" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/app-password-confirm password $MYSQL_ROOT_PASS" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/mysql/admin-pass password $MYSQL_ROOT_PASS" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/password-confirm password $MYSQL_ROOT_PASS" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/setup-password password $MYSQL_ROOT_PASS" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/database-type select mysql" | debconf-set-selections
	echo "phpmyadmin phpmyadmin/mysql/app-pass password $MYSQL_ROOT_PASS" | debconf-set-selections
	apt-get -y install phpmyadmin
	service apache2 restart
fi