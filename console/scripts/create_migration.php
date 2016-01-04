<?php

/**
 * Create Migration
 * console/console CreateMigration --data='{"description":"migration for migration"}'
 */
class CreateMigration extends AppShell {

	public $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new Migration($this->Session);
		$this->Migration->setBaseDirectory('database');
	}

	public function main() {
		$this->debug('Start Migration DB');
		$this->debug("Creating file for '{$this->data['description']}' on " . $this->Migration->getFileMigrationDirectory() . " directory");

		$file_name = $this->Migration->create($this->data['description']);

		$this->out("The file '{$file_name}'' was created on '" . $this->Migration->getFileMigrationDirectory());
		$this->debug('Finished Migration DB');
	}
}
