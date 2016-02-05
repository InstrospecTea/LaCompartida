<?php

/**
 * Rollback Migration
 * console/console rollback_migration [--debug]
 */
class RollbackMigration extends AppShell {

	private $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new \Database\Migration();

		if (!$this->Migration->schemaExists()) {
			$this->out('Creating migration schema');
			$this->Migration->createSchema();
		}
	}

	public function main() {
		$this->debug('Start Rollback Migration');

		$rollback_batch = $this->Migration->getLastBatchNumber();
		$files = $this->Migration->getFilesRollbackMigration($rollback_batch);

		if (!empty($files)) {
			foreach ($files as $file_name) {
				$this->out("Running rollback {$file_name}");

				require_once $this->Migration->getMigrationDirectory() . "/{$file_name}";
				$class_name = $this->Migration->getClassNameByFileName($file_name);

				$ReflectedClass = new ReflectionClass("Database\\$class_name");
				$CustomMigration = $ReflectedClass->newInstance();
				$CustomMigration->down();
				$CustomMigration->runDown();

				$this->Migration->registerRollback($file_name);
			}
		}

		$this->debug('Finished Rollback Migration');
	}
}
