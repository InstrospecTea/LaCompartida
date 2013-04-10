<?php
require_once dirname(__FILE__) . '/../app/conf.php';

define(MIN_TIMESTAMP, 315532800);
define(MAX_TIMESTAMP, 4182191999);
function isValidTimeStamp($timestamp) {
  return ($timestamp >= MIN_TIMESTAMP)
  && ($timestamp <= MAX_TIMESTAMP);
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

?>
<html>
  <head>
    <meta name=”viewport” content=”width=device-width, initial-scale=1″ />
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        font-family: Arial;
        font-size: 8pt !important;
        text-align: center;
      }

      .semana_del_dia,
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
        top: 0;
        z-index: 999;
        padding: 5pt 0;
      }

      #cabecera_dias .diasemana {
        width: 16%;
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
      }

      #celdastrabajo .celdadias {
        width: 16%;
        height: 100%;
        float: left;
      }

      #celdastrabajo #celdadia3,
      #celdastrabajo #celdadia5 {
        background-color: #F0F0F0;
      }

      #celdastrabajo #celdadia7,
      #celdastrabajo #celdadia1 {
        width: 10%;
      }

      #celdastrabajo .cajatrabajo {
        width: 95% !important;
        font-size: 14pt !important;
        border: 0pt solid black !important;
        border-radius: 5pt !important;
        padding: 2pt !important;
        min-height: 88pt;
        margin: 3pt auto;
      }

      #celdastrabajo .totaldia,
      .total_semana_actual {
        width: 16%;
        position: fixed;
        bottom: 0;
        padding: 5pt 0;
      }

      #celdastrabajo #celdadia7 .totaldia,
      #celdastrabajo #celdadia1 .totaldia {
        display: none;
      }

      #cabecera_dias,
      #celdastrabajo .totaldia,
      .total_semana_actual {
        /*background-color: #2A323F;*/
        background-image: linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
        background-image: -o-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
        background-image: -moz-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
        background-image: -webkit-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);
        background-image: -ms-linear-gradient(bottom, rgb(17,22,26) 0%, rgb(56,67,87) 10%, rgb(46,55,70) 50%);

        background-image: -webkit-gradient(
          linear,
          left bottom,
          left top,
          color-stop(0, rgb(17,22,26)),
          color-stop(0.1, rgb(56,67,87)),
          color-stop(0.5, rgb(46,55,70))
        );

        color: #CCCCCC;
      }

      .total_semana_actual {
        width: 20%;
        right: 0;
        text-align: right;
        font-weight: bold;
      }

      /*
       *
       */
      #tooltip
    {
      font-family: Ubuntu, sans-serif;
      font-size: 0.875em;
      text-align: center;
      text-shadow: 0 1px rgba( 0, 0, 0, .5 );
      line-height: 1.5;
      color: #fff;
      background: #333;
      background: -webkit-gradient( linear, left top, left bottom, from( rgba( 0, 0, 0, .7 ) ), to( rgba( 0, 0, 0, .9 ) ) );
      background: -webkit-linear-gradient( top, rgba( 0, 0, 0, .7 ), rgba( 0, 0, 0, .9 ) );
      background: -moz-linear-gradient( top, rgba( 0, 0, 0, .7 ), rgba( 0, 0, 0, .9 ) );
      background: -ms-radial-gradient( top, rgba( 0, 0, 0, .7 ), rgba( 0, 0, 0, .9 ) );
      background: -o-linear-gradient( top, rgba( 0, 0, 0, .7 ), rgba( 0, 0, 0, .9 ) );
      background: linear-gradient( top, rgba( 0, 0, 0, .7 ), rgba( 0, 0, 0, .9 ) );
      -webkit-border-radius: 5px;
      -moz-border-radius: 5px;
      border-radius: 5px;
      border-top: 1px solid #fff;
      -webkit-box-shadow: 0 3px 5px rgba( 0, 0, 0, .3 );
      -moz-box-shadow: 0 3px 5px rgba( 0, 0, 0, .3 );
      box-shadow: 0 3px 5px rgba( 0, 0, 0, .3 );
      position: absolute;
      z-index: 100;
      padding: 15px;
      text-align: left;
    }
      #tooltip:after
      {
        width: 0;
        height: 0;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-top: 10px solid #333;
        border-top-color: rgba( 0, 0, 0, .7 );
        content: '';
        position: absolute;
        left: 50%;
        bottom: -10px;
        margin-left: -10px;
      }
        #tooltip.top:after
        {
          border-top-color: transparent;
          border-bottom: 10px solid #333;
          border-bottom-color: rgba( 0, 0, 0, .6 );
          top: -20px;
          bottom: auto;
        }
        #tooltip.left:after
        {
          left: 10px;
          margin: 0;
        }
        #tooltip.right:after
        {
          right: 10px;
          left: auto;
          margin: 0;
        }
    </style>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script src="//static.thetimebilling.com/js/bottom.js"></script>
  </head>
  <body>
<?php
// El nombre es para que el include funcione
$id_usuario = $user_token_data->user_id;

include APPPATH . '/app/interfaces/ajax/semana_ajax.php';
?>
    <script>
      $(document).ready(function () {
        $('.pintame').each(function() {
          $(this).css('background-color', window.top.s2c($(this).attr('rel')));
        });

        var targets = $( '.cajatrabajo' ),
        target  = false,
        tooltip = false,
        title   = false;

        targets.each(function (idx, el) {
          tip = $(el).attr( 'onmouseover' );
          tip = tip.replace("ddrivetip('", "");
          tip = tip.replace("')", "");
          tip = tip.replace(/<b>.*?<\/b><br>/g, "");
          $(el).attr('data-title', tip);
          $(el).removeAttr('onmouseover');
          $(el).removeAttr('onmouseout');
        });

    targets.bind( 'mouseenter', function(event)
    {
        event.preventDefault();
        target  = $( this );
        //tip = tip.replace(/<b>.*<\/b>/gm, "");
        tooltip = $( '<div id="tooltip"></div>' );
        tip = target.attr('data-title');
        if( !tip || tip == '' )
            return false;

        target.removeAttr( 'title' );
        tooltip.css( 'opacity', 0 )
               .html( tip )
               .appendTo( 'body' );

        var init_tooltip = function()
        {
            if( $( window ).width() < tooltip.outerWidth() * 1.5 )
                tooltip.css( 'max-width', $( window ).width() / 2 );
            else
                tooltip.css( 'max-width', 340 );

            var pos_left = target.offset().left + ( target.outerWidth() / 2 ) - ( tooltip.outerWidth() / 2 ),
                pos_top  = target.offset().top - tooltip.outerHeight() - 20;

            if( pos_left < 0 )
            {
                pos_left = target.offset().left + target.outerWidth() / 2 - 20;
                tooltip.addClass( 'left' );
            }
            else
                tooltip.removeClass( 'left' );

            if( pos_left + tooltip.outerWidth() > $( window ).width() )
            {
                pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                tooltip.addClass( 'right' );
            }
            else
                tooltip.removeClass( 'right' );

            if( pos_top < 0 )
            {
                var pos_top  = target.offset().top + target.outerHeight();
                tooltip.addClass( 'top' );
            }
            else
                tooltip.removeClass( 'top' );

            tooltip.css( { left: pos_left, top: pos_top } )
                   .animate( { top: '+=10', opacity: 1 }, 50 );
        };

        init_tooltip();
        $( window ).resize( init_tooltip );

        var remove_tooltip = function()
        {
            tooltip.animate( { top: '-=10', opacity: 0 }, 50, function()
            {
                $( this ).remove();
            });

            target.attr( 'title', tip );
        };

        target.bind( 'mouseleave', remove_tooltip );
        tooltip.bind( 'click', remove_tooltip );
    });
      });
    </script>
  </body>
</html>