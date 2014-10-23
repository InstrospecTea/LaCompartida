<?php
$messageManager = new MessageManager();

foreach($messageManager->getMessages() as $message) {

	switch($message->type()) {
		case 'I':
			$alertMessage = 'Información:';
			break;
		case 'E':
			$alertMessage = 'Error:';
			break;
		case 'S':
			$alertMessage = 'Éxito:';
			break;
		default:
			$alertMessage = '';
			break;
	}

?>

	<table width="80%" class="alerta">
		<tbody>
			<tr>
				<td valign="top" align="left" style="font-size: 12px;">
					<strong><?php echo $alertMessage ?></strong>
					<br/>
					<?php echo $message->content(); ?>
					<br/>
				</td>
			</tr>
		</tbody>
	</table>
	<br/>
<?php }
$messageManager->cleanMessageQueue();