<?php

/**
 * Run Migration
 * console/console run_migration [--debug]
 */
class RunMigration extends AppShell {

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
		$this->debug('Start Run Migration');

		$files = $this->Migration->getFilesMigration();
		$next_batch = $this->Migration->getNextBatchNumber();

		if (!empty($files)) {
			foreach ($files as $file_name) {
				if ($this->Migration->isRunnable($file_name)) {
					$this->out("Running {$file_name}");

					require_once $this->Migration->getMigrationDirectory() . "/{$file_name}";
					$class_name = $this->Migration->getClassNameByFileName($file_name);

					$ReflectedClass = new ReflectionClass("Database\\$class_name");
					$CustomMigration = $ReflectedClass->newInstance();
					$CustomMigration->up();
					$CustomMigration->runUp();

					$this->Migration->registerMigration($file_name, $next_batch);
				}
			}
		}

		$this->debug('Finished Run Migration');
	}
}
