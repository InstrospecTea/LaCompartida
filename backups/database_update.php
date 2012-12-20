#!/usr/bin/php
<?php
/* Run modes
 *
 * To update all dbs marked as "for update":
 *   php database_udpate.php [debug/update_db]
 * To update one db:
 *   php database_udpate.php [debug/update_db] [subdominio.subdir] [0/1]
 *
 * Samples:
 *    Real for all marked:  php database_udpate.php update_db
 *    Real for one marked:  php database_update.php update_db lemontech.time_tracking_release 1
 *    Fake for one test:    php database_update.php debug lemontech.time_tracking
 *
 */

require_once dirname(__FILE__) . '/DatabaseUpdater.php';

$db_updater = new DatabaseUpdater(
	'c85ef9997e6a30032a765a20ee69630b',
	array(
		'key' => 'AKIAJDGKILFBFXH3Y2UA',
		'secret' => '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',
		'default_cache_config' => '/var/www/cache/dynamoDBbackups'
	)
);

$db_updater->update($argv[1], $argv[2], $argv[3]);