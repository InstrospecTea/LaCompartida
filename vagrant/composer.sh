#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

# Instalacion de composer
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin
    mv /usr/local/bin/composer.phar /usr/local/bin/composer
    chmod 755 /usr/local/bin/composer
fi