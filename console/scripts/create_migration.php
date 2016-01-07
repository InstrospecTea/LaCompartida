<?php

/**
 * Create Migration
 * console/console CreateMigration --data='{"description":"migration for migration"}'
 */
class CreateMigration extends AppShell {

	private $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new \Database\Migration($this->Session);
	}

	public function main() {
		$this->debug('Start Create Migration');
		$this->debug("Creating file for '{$this->data['description']}' on " . $this->Migration->getMigrationDirectory() . " directory");

		$file_name = $this->Migration->create($this->data['description']);

		$this->out("The file '{$file_name}'' was created on '" . $this->Migration->getMigrationDirectory());
		$this->debug('Finished Create Migration');
	}
}
