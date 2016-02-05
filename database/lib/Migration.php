<?php

namespace Database;

use \PDO;
use \PDOException;
use \Database\Conf as DatabaseConf;

class Migration {

	private $query_up;
	private $query_down;
	private $root_directory;
	private $Database;
	private $files_ignore;

	public function __construct() {
		$this->query_up = array();
		$this->query_down = array();
		$this->root_directory = __BASEDIR__;
		$this->files_ignore = array('..', '.', '.gitkeep');

		$dsn = 'mysql:dbname=' . \Conf::dbName() . ';host=' . \Conf::dbHost();
		$this->Database = new PDO($dsn, DatabaseConf::getUserName(), DatabaseConf::getPassword());
		$this->Database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
		$string = strtolower($description);
		$string = str_replace(' ', '_', $string);
		return $string;
	}

	private function sanitizeDescriptionForClassName($description) {
		$string = ucwords(strtolower($description));

		foreach (array('-', '\'') as $delimiter) {
			if (strpos($string, $delimiter) !== false) {
				$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
			}
		}

		$string = str_replace(' ', '', $string);

		return $string;
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
		$this->executeQuery($this->query_up);
	}

	public function runDown() {
		$this->executeQuery($this->query_down);
	}

	private function executeQuery($query) {
		if (!empty($query)) {
			if (is_array($query)) {
				array_walk($query, array('self', 'executeQuery'));
			} else {
				try {
					$Statement = $this->Database->prepare($query);
					$Statement->execute();
				} catch (PDOException $e) {
					// TODO: Registrar errores
				}
			}
		}
	}

	private function getResultsQuery($query) {
		$results = null;

		try {
			$Statement = $this->Database->prepare($query);
			$Statement->execute();
			$results = $Statement->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			// TODO: Registrar errores
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

		preg_match('/^([0-9]*)_(.*)/', $file_name, $matches);

		$description = str_replace('_', ' ', $matches[2]);
		$description = str_replace('.php', '', $description);

		if (!empty($matches)) {
			$descompose_file_name['version'] = (int) $matches[1];
			$descompose_file_name['description'] = $description;
			$descompose_file_name['class_name'] = $this->sanitizeDescriptionForClassName($description);
		}

		return $descompose_file_name;
	}

	/**
	 * Obtiene un listado ordenado por defecto de forma ascendente
	 * @param  integer $sorting_order 0: ascendente 1: descendente
	 * @return array listado de archivos
	 */
	public function getFilesMigration($sorting_order = 0) {
		$files = array_diff(scandir($this->getMigrationDirectory(), $sorting_order), $this->files_ignore);
		return $files;
	}

	public function getLastFileMigration() {
		$files = $this->getFilesMigration(1);
		return !empty($files) ? $files[0] : '';
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
		$file = $this->getResultsQuery('SELECT `migration` AS file_name FROM `migrations` ORDER BY 1 DESC LIMIT 1');

		if (!empty($file)) {
			$file = $this->decomposeFileName($file[0]['file_name']);
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
}
