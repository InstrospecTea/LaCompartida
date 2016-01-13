<?php

/**
 * Create Migration
 * console/console create_migration --data='{"description":"migration for migration"}' [--debug]
 */
class CreateMigration extends AppShell {

	private $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new \Database\Migration($this->Session);

		if (!$this->Migration->schemaExists()) {
			$this->out('Creating migration schema');
			$this->Migration->createSchema();
		}
	}

	public function main() {
		$this->debug('Start Create Migration');
		$this->debug("Creating file for '{$this->data['description']}' on " . $this->Migration->getMigrationDirectory() . ' directory');

		$file_name = $this->Migration->create($this->data['description']);

		$this->out("The file '{$file_name}'' was created on '" . $this->Migration->getMigrationDirectory());
		$this->debug('Finished Create Migration');
	}
}
