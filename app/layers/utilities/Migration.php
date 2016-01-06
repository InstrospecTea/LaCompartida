<?php

class Migration {

	protected $Session;
	private $query_up;
	private $query_down;

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
		$this->query_up = array();
		$this->query_down = array();
	}

	public function getBaseDirectory() {
		return __BASEDIR__ . '/database';
	}

	public function getFileMigrationDirectory() {
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
		$pfile = fopen($this->getFileMigrationDirectory() . "/{$file_name}", 'w') or die('Unable to open file!');
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
		$template = file_get_contents($this->getBaseDirectory() . '/TemplateMigration.php');
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
				$Statement = $this->Session->pdodbh->prepare($query);
				$Statement->execute();
			}
		}
	}

	private function getResultsQuery($query) {
		$Statement = $this->Session->pdodbh->prepare($query);
		$Statement->execute();
		return $Statement->fetchAll(PDO::FETCH_ASSOC);
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

	public function getFilesMigration() {
		$files = array_diff(scandir($this->getFileMigrationDirectory()), array('..', '.'));
		return $files;
	}

	public function registerMigration($file_name, $batch) {
		$this->executeQuery("INSERT INTO `migrations` SET `migration` = '{$file_name}', `batch` = '{$batch}'");
	}

	public function getNextBatchNumber() {
		$migrations = $this->getResultsQuery('SELECT MAX(`batch`) max_batch FROM `migrations`');
		$netx_batch = (int) $migrations[0]['max_batch'] + 1;
		return $netx_batch;
	}
}
