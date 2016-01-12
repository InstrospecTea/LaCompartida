<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$hash = !empty($_GET['hash']) ? $_GET['hash'] : (!empty($argv[1]) ? $argv[1] : '');

if (empty($hash) || $hash != \Conf::Hash()) {
	throw new Exception('Invalid credentials');
}

$Migration = new \Database\Migration();

if (!$Migration->schemaExists()) {
	$Migration->createSchema();
}

echo '<p>Start Run Migration</p>';

$files = $Migration->getFilesMigration();
$batch = $Migration->getNextBatchNumber();

if (!empty($files)) {
	foreach ($files as $file_name) {
		if ($Migration->isRunnable($file_name)) {
			echo "<p>Running {$file_name}</p>";

			require_once $Migration->getMigrationDirectory() . "/{$file_name}";
			$class_name = $Migration->getClassNameByFileName($file_name);

			$ReflectedClass = new ReflectionClass("Database\\$class_name");
			$CustomMigration = $ReflectedClass->newInstance();
			$CustomMigration->up();
			$CustomMigration->runUp();

			$Migration->registerMigration($file_name, $batch);
		}
	}
}

echo '<p>Finished Run Migration</p>';
