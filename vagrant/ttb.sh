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
echo "AddDefaultCharset ISO-8859-1" >> /etc/apache2/conf.d/charset
echo "AddCharset ISO-8859-1 .iso8859-1 .latin1" >> /etc/apache2/conf.d/charset

# PHP

# Todas las siguentes configuraciones son necesarias
sed -i "s/html_errors = Off/html_errors = On/" /etc/php5/apache2/php.ini
sed -i "s/short_open_tag = Off/short_open_tag = On/g" /etc/php5/apache2/php.ini
sed -i 's/;default_charset = "iso-8859-1"/default_charset = "iso-8859-1"/g' /etc/php5/apache2/php.ini
sed -i "s/error_reporting = E_ALL & ~E_DEPRECATED/error_reporting = E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR/g" /etc/php5/apache2/php.ini
sed -i "s/register_globals = Off/register_globals = On/g" /etc/php5/apache2/php.ini

# Configuracion local de la conexion a la base de datos
if [ ! -f /vagrant/ttb/app/miconf.php ]; then
    if [ -f /vagrant/ttb/app/miconf.php.default ]; then
        cp /vagrant/ttb/app/miconf.php.default /vagrant/ttb/app/miconf.php
        sed -i "s/lapass/admin.asdwsx/" /vagrant/ttb/app/miconf.php
    fi
fi

# Instalar vendors
cd /vagrant/ttb && /usr/local/bin/composer install

# Bibliotecas pear para TTB Clasic
pear install Numbers_Words-0.16.4
pear install Spreadsheet_Excel_Writer-beta
pear install OLE-0.5

# Instalar wkhtmltopdf
apt-get update
apt-get install libfontenc1 libxfont1 xfonts-75dpi xfonts-base xfonts-encodings xfonts-utils fontconfig libxrender1 -y
wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-precise-i386.deb -P /tmp
dpkg -i /tmp/wkhtmltox-0.12.2.1_linux-precise-i386.deb

# Actualizar la base de datos de ejemplo
curl -I "http://localhost/ttb/app/update.php?hash=c85ef9997e6a30032a765a20ee69630b"
