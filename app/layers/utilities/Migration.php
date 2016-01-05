<?php

class Migration {

	protected $Session;
	private $query_up;
	private $query_down;
	private $version;

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
		$this->query_up = array();
		$this->query_down = array();
		$this->setSystemVersion();
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
		$this->run($this->query_up);
	}

	public function runDown() {
		$this->run($this->query_down);
	}

	public function run($query) {
		if (!empty($query)) {
			if (is_array($query)) {
				array_walk($query, array('self', 'run'));
			} else {
				$Statement = $this->Session->pdodbh->prepare($query);
				$Statement->execute();
			}
		}
	}

	public function getClassNameByFileName($file_name) {
		$file = $this->decomposeFileName($file_name);
		return $file['class_name'];
	}

	public function isRunnable($file_name) {
		$is_runnable = false;
		$file = $this->decomposeFileName($file_name);

		if (!empty($file)) {
			$is_runnable = $file['version'] > $this->version;
		}

		return $is_runnable;
	}

	private function setSystemVersion() {
		$this->system_version = (int) file_get_contents(__BASEDIR__ . '/MIGRATION');
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
}
