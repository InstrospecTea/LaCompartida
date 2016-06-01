<?php

namespace Database;

use \PDO;
use \PDOException;
use \Database\Config as MigrationConfig;
use \Database\MigrationMailing;
use \TTB\Utiles;
use \UtilesApp;

class Migration {

	private $query_up;
	private $query_down;
	private $root_directory;
	private $Database;
	private $files_ignore;
	private $MigrationMailing;
	private $debug;

	public function __construct() {
		$this->query_up = array();
		$this->query_down = array();
		$this->root_directory = __BASEDIR__;
		$this->files_ignore = array('..', '.', '.gitkeep');
		$this->debug = false;
		$dsn = "mysql:dbname={$this->getDatabaseName()};host={$this->getHostName()}";
		$this->Database = new PDO($dsn, MigrationConfig::get('user_name'), MigrationConfig::get('password'));
		$this->Database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->MigrationMailing = new MigrationMailing();
	}

	public function setDebug($is_debug) {
		$this->debug = $is_debug;
	}

	private function getDatabaseName() {
		return \Conf::dbName();
	}

	private function getHostName() {
		return \Conf::dbHost();
	}

	public function setRootDirectory($directory) {
		$this->root_directory = $directory;
	}

	public function getBaseDirectory() {
		return "{$this->root_directory}/database";
	}

	public function getLibraryDirectory() {
		return $this->getBaseDirectory() . '/lib';
	}

	public function getMigrationDirectory() {
		return $this->getBaseDirectory() . '/migrations';
	}

	public function create($description) {
		$file_name = $this->sanitizeDescriptionForFileName($description);
		$file_name = date('YmdHis') . "_{$file_name}.php";

		$template = $this->getTemplate($description);
		$this->createFile($file_name, $template);

		return $file_name;
	}

	private function createFile($file_name, $template) {
		$pfile = fopen($this->getMigrationDirectory() . "/{$file_name}", 'w') or die('Unable to open file!');
		fwrite($pfile, $template);
		fclose($pfile);
	}

	private function sanitizeDescriptionForFileName($description) {
		$slugged = UtilesApp::slug($description);
		return Utiles::underscoreize($slugged);
	}

	private function sanitizeDescriptionForClassName($description) {
		$file_name = $this->sanitizeDescriptionForFileName($description);
		return Utiles::Pascalize($file_name);
	}

	private function getTemplate($description) {
		$class_name = $this->sanitizeDescriptionForClassName($description);
		$template = file_get_contents($this->getLibraryDirectory() . '/TemplateMigration');
		return str_replace('CLASSNAME', $class_name, $template);
	}

	public function addQueryUp($query) {
		if (!empty($query)) {
			$this->query_up[] = $query;
		}
	}

	public function addQueryDown($query) {
		if (!empty($query)) {
			$this->query_down[] = $query;
		}
	}

	public function runUp() {
		$this->runTransaction($this->query_up);
	}

	public function runDown() {
		$this->runTransaction($this->query_down);
	}

	private function executeQuery($query) {
		if (empty($query)) {
			return;
		}
		$Statement = $this->Database->prepare($query);
		$Statement->execute();
	}

	private function runTransaction($query) {
		if (empty($query)) {
			return;
		}
		try {
			$this->Database->beginTransaction();
			if (is_array($query)) {
				array_walk($query, array($this, 'executeQuery'));
			} else {
				$this->executeQuery($query);
			}
			$this->Database->commit();
		} catch (PDOException $e) {
			$this->Database->rollBack();
			$separator = ($this->debug) ? "\n" : '<br/>';
			$message = $this->buildMessage($e, $query, $separator);

			if ($this->debug) {
				echo $message;
			} else {
				$this->MigrationMailing->send($message);
			}

			exit;
		}
	}

	public function buildMessage(PDOException $e, $query = '', $separator = '<br/>') {
		$message = "Error en la ejecución de instruccion SQL: %t%"
		. "{$e->getMessage()} %t%"
		. "Code: {$e->getCode()} %t%"
		. "Host: {$this->getHostName()} %t%"
		. "Database: {$this->getDatabaseName()} %t%"
		. "Query: {$query}\n";

		return str_replace('%t%', $separator, $message);
	}

	public function getResultsQuery($query) {
		try {
			$Statement = $this->Database->prepare($query);
			$Statement->execute();
			$results = $Statement->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			// TODO: Registrar errores
			$results = null;
		}

		return $results;
	}

	public function getClassNameByFileName($file_name) {
		$file = $this->decomposeFileName($file_name);
		return $file['class_name'];
	}

	public function isRunnable($file_name) {
		$is_runnable = false;
		$file = $this->decomposeFileName($file_name);

		if (!empty($file)) {
			$migration = $this->getResultsQuery("SELECT `migration`, `batch` FROM `migrations` WHERE `migration` = '{$file_name}'");
			$is_runnable = empty($migration);
		}

		return $is_runnable;
	}

	private function decomposeFileName($file_name) {
		$descompose_file_name = array(
			'version' => 0,
			'description' => '',
			'class_name' => ''
		);

		preg_match('/^([0-9]*)_([^.]+).*/', $file_name, $matches);

		$description = Utiles::humanize($matches[2]);

		if (!empty($matches)) {
			$descompose_file_name['version'] = $matches[1];
			$descompose_file_name['description'] = $description;
			$descompose_file_name['class_name'] = $this->sanitizeDescriptionForClassName($description);
		}

		return $descompose_file_name;
	}

	/**
	 * Obtiene un listado ordenado por defecto de forma ascendente
	 * @return array listado de archivos
	 */
	public function getFilesMigration() {
		$files = array_diff(scandir($this->getMigrationDirectory()), $this->files_ignore);
		sort($files);
		return $files;
	}

	public function getLastFileMigration() {
		$files = $this->getFilesMigration();
		$file = array_pop($files);
		return !empty($file) ? $file : '';
	}

	public function getFilesRollbackMigration($batch) {
		$files = array();
		$migrations = $this->getResultsQuery("SELECT `migration` FROM `migrations` WHERE `batch` = '{$batch}'");
		if (!empty($migrations)) {
			foreach ($migrations as $file) {
				$files[] = $file['migration'];
			}
		}
		return $files;
	}

	public function registerMigration($file_name, $batch) {
		$this->executeQuery("INSERT INTO `migrations` SET `migration` = '{$file_name}', `batch` = '{$batch}'");
	}

	public function registerRollback($file_name) {
		$this->executeQuery("DELETE FROM `migrations` WHERE `migration` = '{$file_name}'");
	}

	private function getMaxBatchNumber() {
		$max_batch = 0;
		$migration = $this->getResultsQuery('SELECT MAX(`batch`) AS max_batch FROM `migrations`');
		if (!empty($migration)) {
			$max_batch = (int) $migration[0]['max_batch'];
		}
		return $max_batch;
	}

	public function getNextBatchNumber() {
		return $this->getMaxBatchNumber() + 1;
	}

	public function getLastBatchNumber() {
		return $this->getMaxBatchNumber();
	}

	public function getLastVersionOnDatabase() {
		$version = 0;
		$files = $this->getResultsQuery('SELECT `migration` AS file_name FROM `migrations` ORDER BY 1 DESC LIMIT 1');

		if (!empty($files)) {
			$file = $this->decomposeFileName($files[0]['file_name']);
			$version = $file['version'];
		}

		return $version;
	}

	public function getLastVersionOnFileSystem() {
		$file = $this->decomposeFileName($this->getLastFileMigration());
		return $file['version'];
	}

	public function schemaExists() {
		$migration = $this->getResultsQuery('SHOW TABLES LIKE "migrations"');
		return !empty($migration);
	}

	public function createSchema() {
		$this->executeQuery(
			'CREATE TABLE `migrations` (
			  `migration` varchar(255) NOT NULL,
			  `batch` int(11) NOT NULL,
			  PRIMARY KEY (`migration`),
			  KEY `migrations_batch` (`batch`)
			) ENGINE = InnoDB'
		);
	}

	public function getUnexecuted() {
		$migration_in_db = $this->getDatabaseMigrations();
		$migration_in_fs = $this->getFilesMigration();
		$files = array_diff($migration_in_fs, $migration_in_db);
		sort($files);
		return $files;
	}

	public function getDatabaseMigrations() {
		$result = array();
		$rows = $this->getResultsQuery('SELECT `migration` FROM `migrations` order by 1');
		foreach ($rows as $row) {
			$result[] = $row['migration'];
		}
		return $result;
	}

}
