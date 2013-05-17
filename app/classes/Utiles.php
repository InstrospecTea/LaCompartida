<?php
namespace TTB;
require_once dirname(__FILE__) . '/../conf.php';
use \Conf;
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
use \TipoCorreo as TipoCorreo;


class Utiles extends \Utiles {


	function send_mail($emailaddress, $fromname, $fromaddress, $emailsubject, $body, $attachments = false, $type_content = 'txt') {
		$eol = "\r\n";
		$mime_boundary = md5(time());

		# Common Headers
		$headers .= 'From: ' . $fromname . '<' . $fromaddress . '>' . $eol;
		$headers .= 'Reply-To: ' . $fromname . '<' . $fromaddress . '>' . $eol;
		$headers .= 'Return-Path: ' . $fromname . '<' . $fromaddress . '>' . $eol;	// these two to set reply address
		$headers .= "Message-ID: <" . $now . " TheSystem@" . $_SERVER['SERVER_NAME'] . ">" . $eol;
		$headers .= "X-Mailer: PHP v" . phpversion() . $eol;		  // These two to help avoid spam-filters
		# Boundry for marking the split & Multitype Headers
		$headers .= 'MIME-Version: 1.0' . $eol;
		$headers .= "Content-Type: multipart/related; boundary=\"" . $mime_boundary . "\"" . $eol;

		$msg = "";

		if ($attachments !== false) {

			for ($i = 0; $i < count($attachments); $i++) {
				if (is_file($attachments[$i]["file"])) {
					# File for Attachment
					$file_name = substr($attachments[$i]["file"], (strrpos($attachments[$i]["file"], "/") + 1));

					$handle = fopen($attachments[$i]["file"], 'rb');
					$f_contents = fread($handle, filesize($attachments[$i]["file"]));
					$f_contents = chunk_split(base64_encode($f_contents));	//Encode The Data For Transition using base64_encode();
					fclose($handle);

					# Attachment
					$msg .= "--" . $mime_boundary . $eol;
					$msg .= "Content-Type: " . $attachments[$i]["content_type"] . "; name=\"" . $file_name . "\"" . $eol;
					$msg .= "Content-Transfer-Encoding: base64" . $eol;
					$msg .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"" . $eol . $eol; // !! This line needs TWO end of lines !! IMPORTANT !!
					$msg .= $f_contents . $eol . $eol;
				}
			}
		}

		# Setup for text OR html
		#       $msg .= "Content-Type: multipart/alternative".$eol;

		if ($type_content == 'txt') {
			# Text Version
			$msg .= "--" . $mime_boundary . $eol;
			$msg .= "Content-Type: text/plain; charset=iso-8859-1" . $eol;
			$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			$msg .= strip_tags(str_replace("<br>", "\n", $body)) . $eol . $eol;
		} else {
			# HTML Version
			$msg .= "--" . $mime_boundary . $eol;
			$msg .= "Content-Type: text/html; charset=iso-8859-1" . $eol;
			$msg .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			$msg .= $body . $eol . $eol;
		}

		# Finished
		$msg .= "--{$mime_boundary}--{$eol}{$eol}";  // finish with two eol's for better security. see Injection.
		# SEND THE EMAIL
		ini_set(sendmail_from, $fromaddress);  // the INI lines are to force the From Address to be used !
		$respuesta = mail($emailaddress, $emailsubject, $msg, $headers);
		ini_restore(sendmail_from);
		return $respuesta;
	}

	/*
	  Funcion que inserta en la cola de correos. Revisa tambi�n si el correo no se repite en un d�a siempre y cuando
	  sea diario. En el caso contrario no chequea el d�a simplemente el mensaje.
	  Esto esta hecho para las tareas que pueden tener muchas modificaciones y no se deben repetir los correos.
	 */

	function Insertar($sesion, $subject, $mensaje, $email, $nombre, $es_diario = true, $id_usuario = null, $tipo = null) {
		$id_tipo_correo = null;
		if (!empty($tipo)) {
			$TipoCorreo = new TipoCorreo($sesion);
			$id_tipo_correo = $TipoCorreo->obtenerId($tipo);
		}
		$where_dia = '';
		if ($es_diario) {
			$where_dia = ' AND YEAR(fecha) = YEAR(NOW()) AND MONTH(fecha) = MONTH(NOW()) AND DAY(fecha) = DAY(NOW())';
		}
		$mensaje = mysql_real_escape_string($mensaje);
		$query = "SELECT COUNT(id_log_correo)
					FROM log_correo
					WHERE subject='{$subject}' AND mail='{$email}' AND mensaje='{$mensaje}' {$where_dia}";
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($count) = mysql_fetch_array($resp2);
		if ($count == 0) {
			$query2 = "INSERT INTO log_correo SET
				subject = '{$subject}',
				mensaje = '{$mensaje}',
				mail = '{$email}',
				nombre = '{$nombre}',
				fecha = NOW()
			";
			if (!empty($id_usuario)) {
				$query2 .= ", id_usuario = '{$id_usuario}', fecha_modificacion = NOW()";
			}
			if (!empty($id_tipo_correo)) {
				$query2 .= ", id_tipo_correo = '{$id_tipo_correo}'";
			}
			mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
		}
	}

 
  function camelize($word) { 
    return preg_replace('/(_)([a-z])/e', 'strtoupper("\\2")', $word); 
  }
  
  function pascalize($word) { 
    return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word); 
  }
  
}

