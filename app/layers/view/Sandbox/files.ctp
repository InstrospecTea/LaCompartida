<h1>Sandbox!</h1>

<?php
	if (!empty($temp)) {
		while (!$temp->eof()) {?>
			<p><?php echo $temp->current(); ?></p>
	<?php
			$file->next();
		}
	}
?>
