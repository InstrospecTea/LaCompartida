<?php

/**
 * Class MessageManager
 */
class MessageManager {


	/**
	 *
	 */
	public function __construct() {
		if (!isset($_SESSION['messages'])) {
			$this->cleanMessageQueue();
		}
	}

	/**
	 *
	 * @param FlashMessage $message
	 */
	public function addMessage(FlashMessage $message) {
		$_SESSION['messages'][] = $message;
	}

	/**
	 *
	 */
	public function cleanMessageQueue() {
		$_SESSION['messages'] = array();
	}

	public function getMessages() {
		return $_SESSION['messages'];
	}

	/**
	 *
	 */
	public function printMessages() {
		$messages = $_SESSION['messages'];
		foreach ($messages as $message) {
			$this->printMessage($message);
		}
		$this->cleanMessageQueue();
	}

	/**
	 *
	 * @param FlashMessage $message
	 */
	private function printMessage(FlashMessage $message) {
		echo "<pre>".$message->type()." - ".$message->content()."</pre>";
	}
} 