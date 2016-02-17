<?php

namespace Database;

use \Database\Conf as MigrationConfig;
use \PHPMailer;
use \Exception;

class MigrationMailing {

	private $mail;

	public function __construct() {
		$this->mail = new PHPMailer();
		$this->mail->IsSMTP();
		$this->mail->SMTPAuth = true;
		$this->mail->Host = MigrationConfig::get('mailer.host');
		$this->mail->Username = MigrationConfig::get('mailer.user_name');
		$this->mail->Password = MigrationConfig::get('mailer.password');
		$this->mail->SMTPSecure = 'tls';
		$this->mail->Port = 587;
		$this->mail->CharSet = 'UTF-8';

		$this->mail->setFrom(
			MigrationConfig::get('mailer.sender'),
			MigrationConfig::get('mailer.sender_alias')
		);

		$this->mail->Subject = MigrationConfig::get('mailer.subject');
		$this->mail->isHtml(true);
	}

	public function send($body) {
		$this->mail->addAddress(
			MigrationConfig::get('mailer.receptors')
		);

		$this->mail->Body = $body;
		$isSent = $this->mail->Send();
		if (!$isSent) {
			throw new Exception($this->mail->ErrorInfo);
		}
	}
}
