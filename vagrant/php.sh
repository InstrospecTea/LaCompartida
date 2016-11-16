#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

apt-get -y install php5 php5-curl php-pear libapache2-mod-php5 php5-mysql php5-xdebug

# Configuracion de PHP
cat > $(find /etc/php5 -name xdebug.ini) << EOF
zend_extension=$(find /usr/lib/php5 -name xdebug.so)
xdebug.remote_enable = 1
xdebug.remote_connect_back = 1
xdebug.remote_port = 9000
xdebug.scream=0
xdebug.cli_color=1
xdebug.show_local_vars=1
xdebug.idekey=ide-xdebug
xdebug.profiler_enable=1

; var_dump display
xdebug.var_display_max_depth = 5
xdebug.var_display_max_children = 256
xdebug.var_display_max_data = 1024
EOF
