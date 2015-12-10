#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
MYSQL_ROOT_PASS='admin.asdwsx'

# El siguiente script asume la ya correcta instlacion de MySQL, Apache, PHP, Composer.
# Las siguientes tareas son de configuracion exclusiva de TTB Clasic

# MySQL

# Las base de datos de TTB Clasic estan con encoding LATIN1
echo "[mysqld]" > /etc/mysql/conf.d/character.cnf
echo "character-set-server = latin1" >> /etc/mysql/conf.d/character.cnf
echo "character-set-client = latin1" >> /etc/mysql/conf.d/character.cnf

# TTB Clasic utiliza tablas de MySQL para buscar timezone
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p$MYSQL_ROOT_PASS mysql > /dev/null

# El script update.php usa el usuario admin
mysql -u root -p$MYSQL_ROOT_PASS mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'admin'@'%' IDENTIFIED BY 'admin1awdx'"
mysql -u root -p$MYSQL_ROOT_PASS mysql -e "DROP DATABASE IF EXISTS timetracking"
mysql -u root -p$MYSQL_ROOT_PASS mysql -e "CREATE DATABASE IF NOT EXISTS timetracking"

# Una base de datos de ejemplo
mysql -u root -p$MYSQL_ROOT_PASS timetracking < /vagrant/ttb/vagrant/database.example.sql

# Apache

# TTB Clasic tiene su codigo con encodign ISO-8859-1
echo "AddDefaultCharset ISO-8859-1" >> /etc/apache2/conf-available/charset.conf
echo "AddCharset ISO-8859-1 .iso8859-1 .latin1" >> /etc/apache2/conf-available/charset.conf
sed -i '166 s/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# PHP

# Todas las siguentes configuraciones son necesarias
sed -i "s/html_errors = Off/html_errors = On/" /etc/php5/apache2/php.ini
sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php5/apache2/php.ini
sed -i 's/default_charset = "UTF-8"/default_charset = "iso-8859-1"/g' /etc/php5/apache2/php.ini
sed -i "s/error_reporting = E_ALL & ~E_DEPRECATED/error_reporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR/g" /etc/php5/apache2/php.ini
sed -i "s/max_input_vars = 1000/max_input_vars = 100/g" /etc/php5/apache2/php.ini
service apache2 restart

# Configuracion local de la conexion a la base de datos
if [ ! -f /vagrant/ttb/app/miconf.php ]; then
    if [ -f /vagrant/ttb/app/miconf.php.default ]; then
        cp /vagrant/ttb/app/miconf.php.default /vagrant/ttb/app/miconf.php
        sed -i "s/lapass/admin.asdwsx/" /vagrant/ttb/app/miconf.php
    fi
fi

# Instalar vendors
cd /vagrant/ttb && /usr/local/bin/composer install
composer dump-autoload --optimize

# Actualizar la base de datos de ejemplo
curl -I "http://localhost/ttb/app/update.php?hash=c85ef9997e6a30032a765a20ee69630b"

# Instalar wkhtmltopdf
apt-get update
apt-get install wkhtmltopdf -y
ln -s /usr/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# Corrige config de AWS
cp /vagrant/ttb/backups/AWSSDKforPHP/config-sample.inc.php /vagrant/ttb/backups/AWSSDKforPHP/config.inc.php`
