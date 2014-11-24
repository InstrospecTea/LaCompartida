<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $title_for_layout; ?></title>
		<meta http-equiv="Content-type" content="text/html;charset=ISO-8859-1"/>
		<link rel="shortcut icon" type="image/png" href="<?php echo Conf::RootDir(); ?>/favicon.ico"/>
		<?php
		echo $this->Html->css(Conf::RootDir() . '/app/doc_manager/css/bootstrap.min.css');
		echo $this->Html->css(Conf::RootDir() . '/app/doc_manager/css/bootstrap-theme.min.css');
		echo $this->Html->script_block("var root_dir = '" . Conf::RootDir() . "'");
		echo $this->Html->script('http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js');
		echo $this->Html->script(Conf::RootDir() . '/app/doc_manager/js/bootstrap.min.js');
		echo $this->Html->script(Conf::RootDir() . '/app/doc_manager/js/doc_manager.js');
		?>
	</head>
	<body style="overflow:hidden;">
		<?php echo $content_for_layout; ?>
	</body>
</html>