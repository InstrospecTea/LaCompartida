#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

apt-get install apache2 apache2-utils -y

if [ ! -L /var/www/vagrant ]; then
    sed -i "s/\/var\/www/\/var\/www\/vagrant/" /etc/apache2/sites-available/default
    sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/sites-available/default
    ln -s /vagrant /var/www/vagrant
fi

echo "ServerName localhost" > /etc/apache2/conf.d/name


# Restart
a2enmod rewrite
service apache2 restart