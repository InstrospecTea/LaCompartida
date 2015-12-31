<?php

/**
 * IntegracionMorenoBaldivieso
 * console/console MigrationDb --data="{description}"
 */
class CreateMigration extends AppShell {

	public $Migration;

	public function __construct() {
		parent::__construct();
		$this->Migration = new Migration();
		$this->Migration->setBaseDirectory('database');
	}

	public function main() {
		$this->out('Start Migration DB');
		$this->out("Creating file for '{$this->data['description']}' on " . $this->Migration->getFileMigrationDirectory() . " directory");
		$file_name = $this->Migration->create($this->data['description']);
		$this->out("The file was created on '" . $this->Migration->getFileMigrationDirectory() . "/{$file_name}'");
	}
}
