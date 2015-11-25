#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

apt-get -y install apache2 apache2-utils

if [ ! -L /var/www/vagrant ]; then
    sed -i "s/\/var\/www\/html/\/var\/www\/vagrant/" /etc/apache2/sites-available/000-default.conf
    sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/sites-available/000-default.conf
    ln -s /vagrant /var/www/vagrant
fi

echo "ServerName localhost"


# Restart
a2enmod rewrite
service apache2 restart
