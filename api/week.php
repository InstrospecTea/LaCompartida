<?php
require_once dirname(__FILE__) . '/../app/conf.php';

define(MIN_TIMESTAMP, 315532800);
define(MAX_TIMESTAMP, 4182191999);

function isValidTimeStamp($timestamp) {
	return ($timestamp >= MIN_TIMESTAMP) && ($timestamp <= MAX_TIMESTAMP);
}

$Session = new Sesion(null, true);
$UserToken = new UserToken($Session);

$auth_token = $_REQUEST['AUTHTOKEN'];
$day = $_REQUEST['day'];

$user_token_data = $UserToken->findByAuthToken($auth_token);

// if not exist the auth_token then return error
if (!is_object($user_token_data)) {
	exit('Invalid AUTH_TOKEN');
} else {
	// Login the user
	// $Session->usuario = new Usuario($Sesion);
	// $Session->usuario->LoadId($user_token_data->user_id);
	// $Session->usuario = new UsuarioExt($Session, $Session->usuario->fields['rut']);
	// $Session->logged = true;
}

if (!isset($_REQUEST['day'])) {
	exit('Invalid day');
}

if (!is_null($_REQUEST['day']) && isValidTimeStamp($_REQUEST['day'])) {
	$semana = date('Y-m-d', $_REQUEST['day']);
} else {
	exit("The date format is incorrect");
}

// Week: 7 days; 24 hours; 60 mins; 60secs (7 * 24 * 60 * 60)
$previous_week = $_REQUEST['day'] - 604800;
$next_week = $_REQUEST['day'] + 604800;

?>
<html>
	<head>
		<meta name="viewport" content="width=device-width"/>
		<style>
			html, body {
				height: 100%;
				margin: 0;
				padding: 0;
				font-family: Arial;
				font-size: 8pt !important;
				text-align: center;
			}

			.semana_del_dia {
				background-color: #253546 !important;
				height: 20px !important;
				top: 0;
				position: fixed;
				color: #CCCCCC !important;
				width: 100%;
				text-align: center !important;
				font-size: 10pt !important;
				padding-top: 5px !important;
				overflow: visible !important;
				z-index: 998;
			}

			.total_mes_actual {
				display: none;
			}

			.semanacompleta {
				width: 100%;
				height: 100%;
				margin: 0pt;
				padding: 0pt !important;
				position: relative;
			}

			#cabecera_dias {
				width: 100%;
				position: fixed;
				top: 25px;
				z-index: 996;
				padding: 0;
				background-color: #253546 !important;
				color: #CCCCCC;
			}

			#cabecera_dias .diasemana {
				width: 15.4%;
				float: left;
			}

			#cabecera_dias #dia_5,
			#cabecera_dias #dia_6 {
				width: 10% !important;
			}

			#celdastrabajo {
				position: relative;
				width: 100% !important;
				height: 100%;
				padding: 30pt 0;
				padding-top: 40px !important;
				padding-bottom: 25px !important;
			}

			#celdastrabajo .celdadias {
				width: 15.4%;
				height: 100%;
				float: left;
			}

			#celdastrabajo #celdadia3,
			#celdastrabajo #celdadia5 {
				background-color: #F9F9F9;
			}

			#celdastrabajo #celdadia7,
			#celdastrabajo #celdadia1 {
				width: 10%;
			}

			#celdastrabajo .cajatrabajo {
				font-size: 9pt !important;
				padding: 0px !important;
				padding-top: 5px !important;
				min-height: 30pt;
				margin: 0px;
				border-top: 0px !important;
				border-left: 0px !important;
				border-right: 0px !important;
				border-radius: 5px !important;
				border-bottom: 1px #888 solid !important;
				box-shadow: 0 1px 0 rgba(0,0,0,.05),inset 0 1px 0 white;
				background-color: #ececec;
				background-image: -webkit-linear-gradient(top, #e0e0e0, #d0d0d0);
				font-weight: bold;
			}

			#celdastrabajo .cajatrabajo a {
				text-decoration: none !important;
				color: black;
			}

			.cajatrabajo-odd {
				background-image: -webkit-linear-gradient(top, #A7A7A7, #A9ADA9) !important;
			}

			.cajatrabajo-selected {
				background-image: -webkit-linear-gradient(top, #20ADE7, #20ADE7) !important;
				color: white !important;
			}

			.cajatrabajo-selected a {
				text-decoration: none !important;
				color: white !important;
			}

			#celdastrabajo .totaldia,
			.total_semana_actual {
				width: 15.4%;
				position: fixed;
				bottom: 0;
				padding: 5pt 0;
				-webkit-box-shadow: inset 5px 12px 5px -10px #000000;
			}

			#celdastrabajo #celdadia7 .totaldia,
			#celdastrabajo #celdadia1 .totaldia {
				display: none;
			}

			#celdastrabajo .totaldia,
			.total_semana_actual {
				/*background-color: #2A323F;*/
				background-image: linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
				background-image: -o-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
				background-image: -moz-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
				background-image: -webkit-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(39,55,72) 50%);
				background-image: -ms-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);

				background-image: -webkit-gradient(
					linear,
					left bottom,
					left top,
					color-stop(0, rgb(17,22,26)),
					color-stop(0.1, rgb(56,67,87)),
					color-stop(0.5, rgb(39,55,72))
				);

				color: #CCCCCC;
			}

			.total_semana_actual {
				width: 24%;
				right: 0;
				text-align: right;
				font-weight: bold;
				padding-left: 5px !important;
				padding-right: 10px;
			}

			#tooltip {
				font-family: Ubuntu, sans-serif;
				font-size: 0.875em;
				text-align: center;
				line-height: 1.5;
				color: #333;
				background: #EEE;
				background:  -webkit-linear-gradient(top, #EEE, #CCC);
				-webkit-border-radius: 5px;
				-moz-border-radius: 5px;
				border-radius: 5px;
				border-top: 1px solid #fff;
				-webkit-box-shadow: 0 3px 5px rgba(0, 0, 0, .3);
				-moz-box-shadow: 0 3px 5px rgba(0, 0, 0, .3);
				box-shadow: 0 3px 5px rgba(0, 0, 0, .3);
				position: absolute;
				z-index: 100;
				padding: 15px;
				text-align: left;
			}

			#tooltip:after {
				width: 0;
				height: 0;
				border-left: 10px solid transparent;
				border-right: 10px solid transparent;
				border-top: 10px solid #AAA;
				border-top-color: #CCC;
				content: '';
				position: absolute;
				left: 50%;
				bottom: -10px;
				margin-left: -10px;
			}

			#tooltip.top:after {
				border-top-color: transparent;
				border-bottom: 10px solid #EEE;
				border-bottom-color: #EEE;
				top: -20px;
				bottom: auto;
			}

			#tooltip.left:after {
				left: 10px;
				margin: 0;
			}

			#tooltip.right:after {
				right: 10px;
				left: auto;
				margin: 0;
			}

			.button_left {
				background-image: url('http://static.thetimebilling.com/cartas/img/week_change_left.png');
				background-repeat: no-repeat;
				background-position: center top;
				height: 30px;
			}

			.button_right {
				background-image: url('http://static.thetimebilling.com/cartas/img/week_change_right.png');
				background-repeat: no-repeat;
				background-position: center top;
				height: 30px;
			}
		</style>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="http://static.thetimebilling.com/js/bottom.js"></script>
	</head>
	<body>
		<?php
		// El nombre es para que el include funcione
		$id_usuario = $user_token_data->user_id;
		include APPPATH . '/app/interfaces/ajax/semana_ajax.php';
		?>
		<script>
			var auth_token = '<?php echo $auth_token; ?>';
			var previous_week = '<?php echo $previous_week; ?>';
			var next_week = '<?php echo $next_week; ?>';

			$(document).ready(function () {
				$('.pintame').each(function() {
					$(this).css('background-color', window.top.s2c($(this).attr('rel')));
				});

				// $('#previous_button').html('&larr;');
				// $('#next_button').html('&rarr;');

				$('#previous_button').click(function() {
					location.href = '?AUTHTOKEN=' + auth_token + '&day=' + previous_week;
				});

				$('#next_button').click(function() {
					location.href = '?AUTHTOKEN=' + auth_token + '&day=' + next_week;
				});

				$('.total_semana_actual').text($('.total_semana_actual').text().replace('semana', ''));

				for (var x = 1; x <= 6; x++){
					var total_dia = 0;
					$('.cajatrabajo.dia' + x).each(function(){
						total_dia += $(this).height();
					});
					if (total_dia == 0) {
						$('#celdadia' + x).height('100%');
					} else {
						$('#celdadia' + x).height((100 + total_dia) + 'px');
					}
				}

				var targets = $('.cajatrabajo'),
				target  = false,
				tooltip = false,
				title   = false;

				var color = "";

				targets.each(function (idx, el) {
					tip = $(el).attr('onmouseover');
					tip = tip.replace("ddrivetip('", '');
					tip = tip.replace("')", "");
					tip = tip.replace(/<b>.*?<\/b><br>/g, '');
					$(el).attr('data-title', tip);
					$(el).removeAttr('onmouseover');
					$(el).removeAttr('onmouseout');
					if (idx % 2) {
						$(el).addClass('cajatrabajo-odd');
					}
				 // $(el).attr("style", "font-size:8px; background-color:");
				});

				$('.diasemana').each(function(idx, el){
					var text = $(el).text();
					$(el).html(text.trim().substring(0, 3) + " " + text.split(" ")[1]);
				});

				targets.bind('mouseenter', function(event) {
					event.preventDefault();
					target = $(this);
					//tip = tip.replace(/<b>.*<\/b>/gm, '');
					tooltip = $('<div id="tooltip"></div>');
					tip = target.attr('data-title');

					if (!tip || tip == '') {
						return false;
					}

					target.removeAttr('title');
					tooltip.css('opacity', 0)
								 .html(tip)
								 .appendTo('body');

					var init_tooltip = function() {
						if ($(window).width() < tooltip.outerWidth() * 1.5) {
							tooltip.css('max-width', $(window).width() / 2);
						} else {
							tooltip.css('max-width', 340);
						}

						var pos_left = target.offset().left + (target.outerWidth() / 2) - (tooltip.outerWidth() / 2),
								pos_top  = target.offset().top - tooltip.outerHeight() - 20;

						if (pos_left < 0) {
							pos_left = target.offset().left + target.outerWidth() / 2 - 20;
							tooltip.addClass('left');
						} else{
							tooltip.removeClass('left');
						}

						if (pos_left + tooltip.outerWidth() > $(window).width()) {
							pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
							tooltip.addClass('right');
						} else {
							tooltip.removeClass('right');
						}

						if (pos_top < 0) {
							var pos_top  = target.offset().top + target.outerHeight();
							tooltip.addClass('top');
						} else {
							tooltip.removeClass('top');
						}

						$('.cajatrabajo').removeClass('cajatrabajo-selected');
						target.addClass('cajatrabajo-selected');

						tooltip.css({ left: pos_left, top: pos_top })
									 .animate({ top: '+=10', opacity: 1 }, 50);
					};

					init_tooltip();
					$(window).resize(init_tooltip);

					var remove_tooltip = function() {
						tooltip.animate({ top: '-=10', opacity: 0 }, 50, function() {
							$(this).remove();
						});

						target.attr('title', tip);
						$('.cajatrabajo').removeClass('cajatrabajo-selected');
					};

					target.bind('mouseleave', remove_tooltip);
					tooltip.bind('click', remove_tooltip);
				});
			});
		</script>
	</body>
</html>
