<?php

$messageManager = new MessageManager();

foreach($messageManager->getMessages() as $message) {

	switch($message->type()) {
		case 'I':
			$alertTitle = __('Información');
			$alertType = 'info';
			break;
		case 'E':
			$alertTitle = __('Error');
			$alertType = 'error';
			break;
		case 'S':
			$alertTitle = __('Éxito');
			$alertType = 'success';
			break;
		default:
			$alertTitle = '';
			$alertType = '';
			break;
	}

	$title = $this->Html->tag('span', $alertTitle, array('class' => 'alert-title'));
	$content = $this->Html->tag('span', $message->content(), array('class' => 'alert-content'));
	echo $this->Html->tag('div', $title . $content, array('class' => "alert alert-{$alertType}"));
}
$messageManager->cleanMessageQueue();