<?php
require_once dirname(__FILE__) . '/../app/conf.php';

$Session = new Sesion();
$UserToken = new UserToken($Session);

$auth_token = $_REQUEST['AUTH_TOKEN'];
$user_token_data = $UserToken->findByAuthToken($auth_token);

// if not exist the auth_token then return error
if (!is_object($user_token_data)) {
  exit('Invalid AUTH_TOKEN');
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
        font-size: 14pt !important;
        text-align: center;
      }

      .semana_del_dia,
      .total_mes_actual,
      .total_semana_actual {
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
        background-color: white;
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

      #celdastrabajo #celdadia7,
      #celdastrabajo #celdadia1 {
        width: 10%;
      }

      #celdastrabajo .cajatrabajo {
        width: 95% !important;
        font-size: 14pt !important;
        border: 1pt solid black !important;
        border-radius: 5pt !important;
        padding: 2pt !important;
        min-height: 88pt;
      }

      #celdastrabajo .totaldia {
        width: 16%;
        position: fixed;
        bottom: 0;
        background-color: black;
        color: white;
        padding: 5pt 0;
      }
      #celdastrabajo #celdadia7 .totaldia,
      #celdastrabajo #celdadia7 .totaldia {
        width: 10%;
      }
    </style>
  </head>
  <body>
<?php
// El nombre es para que el include funcione
$id_usuario = $user_token_data->id;
$semana = '2013-02-01';

include APPPATH . '/app/interfaces/ajax/semana_ajax.php';
?>
  </body>
</html>