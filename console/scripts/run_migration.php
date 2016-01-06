<?php

/**
 * Create Migration
 * console/console CreateMigration --data='{"description":"migration for migration"}'
 */
class RunMigration extends AppShell {

	public $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new Migration($this->Session);
	}

	public function main() {
		$this->debug('Start Run Migration');
		$files = $this->Migration->getFilesMigration();
		$batch = $this->Migration->getNextBatchNumber();

		if (!empty($files)) {
			require_once $this->Migration->getBaseDirectory() . "/ITemplateMigration.php";

			foreach ($files as $file_name) {
				if ($this->Migration->isRunnable($file_name)) {
					$this->out("Running {$file_name}");

					require_once $this->Migration->getFileMigrationDirectory() . "/{$file_name}";
					$class_name = $this->Migration->getClassNameByFileName($file_name);

					$ReflectedClass = new ReflectionClass($class_name);
					$CustomMigration = $ReflectedClass->newInstance($this->Session);
					$CustomMigration->up();
					$CustomMigration->runUp();

					$this->Migration->registerMigration($file_name, $batch);
				}
			}
		}

		$this->debug('Finished Run Migration');
	}
}
