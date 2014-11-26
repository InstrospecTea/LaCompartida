<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="iso-8859-1">
		<meta http-equiv="Content-type" content="text/html;charset=ISO-8859-1"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title><?php echo $title_for_layout; ?></title>

		<link rel="shortcut icon" type="image/png" href="<?php echo Conf::RootDir(); ?>/favicon.ico"/>
		<?php
		echo $this->Html->script_block("var root_dir = '" . Conf::RootDir() . "';");
		echo $this->Html->css(Conf::RootDir() . '/public/css/bootstrap.min.css');
		echo $this->Html->css(Conf::RootDir() . '/public/css/bootstrap-theme.min.css');
		echo $this->Html->css(Conf::RootDir() . '/app/layers/assets/css/admin.css');
		echo $this->Html->script('http://code.jquery.com/jquery-1.11.1.min.js');
		echo $this->Html->script(Conf::RootDir() . '/public/js/bootstrap.min.js');
		?>
	</head>
	<body style="overflow:hidden;">
		<?php echo $content_for_layout; ?>
	</body>
</html>