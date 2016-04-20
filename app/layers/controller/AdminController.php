<?php

class AdminController extends AbstractController {

	protected $backup_bucket = 'ttbackups';
	protected $backup_prefix;

	public function __construct() {
		$this->backup_prefix = SUBDOMAIN . '/';
		parent::__construct();
	}

	public function backups() {
		$this->layoutTitle = __('Descarga de Respaldos');
		$S3 = new S3($this->backup_bucket);
		$list = $S3->listBucket($this->backup_prefix);
		$this->set('list', $list);
	}

	public function downloadBackup($file_name) {
		$this->autoRender = false;
		$S3 = new S3($this->backup_bucket);
		$this->redirect($S3->getURL($this->backup_prefix, $file_name));
	}

}
