<?php

class Migration {

	private $directory;
	protected $Session;
	private $query_up;
	private $query_down;

	public function __construct(Sesion $Session) {
		$this->Session = $Session;
		$this->query_up = array();
		$this->query_down = array();
	}

	public function setBaseDirectory($directory) {
		$this->directory = $directory;
	}

	public function getFileMigrationDirectory() {
		return $this->directory . '/migrations';
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

		return $string;
	}

	private function getTemplate($description) {
		$class_name = $this->sanitizeDescriptionForClassName($description);
		$class_name = str_replace(' ', '', $class_name);
		$template = file_get_contents("{$this->directory}/TemplateMigration.php");
		return str_replace('CLASSNAME', $class_name, $template);
	}

	public function addQueryUp($query) {
		if (!empty($query)) {
			array_push($query, $this->query_up);
		}
	}

	public function addQueryDown($query) {
		if (!empty($query)) {
			array_push($query, $this->query_down);
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
				foreach ($query as $_query) {
					$this->run($_query);
				}
			} else {
				$Statement = $this->Session->pdodbh->prepare($_query);
				$Statement->execute();
			}
		}
	}
}
