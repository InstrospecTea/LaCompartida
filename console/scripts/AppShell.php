<?php
abstract class AppShell {
	public abstract function main();

	public function out($v) {
		$me = get_class($this);
		printf("%s: %s\n", $me, print_r($v, true));
	}
}
