<?php

Conf::setStatic('AmazonKey', [
	'key' => 'AKIAJDGKILFBFXH3Y2UA',
	'secret' => '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',
	'region' => 'us-east-1',
	'default_cache_config' => '/var/www/cache/dynamoDBbackups'
]);

Conf::write('dir_temp', '/tmp');
Conf::write('backup_mysql_error', '/var/www/error_logs/backup_mysqlerror.txt');
Conf::write('alerta_disco_temp', 5); //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)
Conf::write('alerta_disco_base', 5); //(GB) si el espacio libre es menos q eso, tira un mensaje (y manda mail)
Conf::write('send_to', 'servidores@lemontech.cl');
Conf::write('dir_temp', '/tmp');
Conf::write('backup_mysql_error', '/tmp/backup_mysqlerror.txt');