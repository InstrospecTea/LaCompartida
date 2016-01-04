<?php

class Migration {

	private $directory;

	public function __construct() {

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

	public function run() {

	}
}
