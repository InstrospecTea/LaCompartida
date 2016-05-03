<?php

/**
 * console/console archivos_db_s3 --domain=stage --subdir=time_tracking --debug > /var/www/error_logs/eliminar_liquidaciones.log
 */
class ArchivosDbS3 extends AppShell {

	private $S3;
	private $max_time = 60; //seconds
	private $timer = 0;
	private $processed = 0;
	private $errors = 0;

	public function main() {
		$this->startTimer();
		$files = $this->getFiles();
		$this->debug('Processing ' . count($files) . ' files.');
		$this->processFiles($files);
		$this->debug("Processed {$this->processed} files.");
	}

	private function processFiles($files) {
		$Archivo = new Archivo($this->Session);
		foreach ($files as $file) {
			if ($this->checkTimeout()) {
				$this->debug("Timeout {$this->max_time} seconds.");
				break;
			}
			++$this->processed;
			if (empty($file->get('codigo_cliente'))) {
				$this->debug("No se encuentra el Cliente de '{$file->get('archivo_nombre')}'.");
				++$this->errors;
				continue;
			}
			$Archivo->Load($file->get('id_archivo'));
			$content = stripslashes($Archivo->fields['archivo_data']);
			$file_fields = ['archivo_data' => null];
			$name = $this->makeFilePath($file_fields, $file, $content);
			if (empty($name)) {
				++$this->errors;
				continue;
			}
			$file_fields['archivo_s3'] = $this->putContentS3($name, $content, $file->get('data_tipo'));
			if (!is_null($file_fields['archivo_s3'])) {
				$Archivo->Fill($file_fields, true);
				$Archivo->Write();
			}
		}
	}

	private function makeFilePath(&$file_fields, Entity $file, $content) {
		$name = false;
		$archivo_nombre = $file->get('archivo_nombre');
		$file_name = UtilesApp::slug(preg_replace('/^([^\.]+).{0,5}/', '$1', $archivo_nombre));
		$file_ext = strpos($archivo_nombre, '.') ? preg_replace('/.*\.(.{2,5})$/', '$1', $archivo_nombre) : '';
		$ext_length = strlen($file_ext);
		if (empty($file_ext) || $ext_length < 3 || $ext_length > 5) {
			$name = true;
			$file_ext = $this->getExtension($file_fields, $file->get('data_tipo'), $content);
		}
		if (empty($file_ext)) {
			$this->debug("El archivo '{$archivo_nombre} [{$file->get('id_archivo')}]' no tiene extensión.");
			return null;
		}
		if ($name) {
			$file_fields['nombre_archivo'] = "{$file_name}.{$file_ext}";
		}
		$codigo_cliente = UtilesApp::slug($file->get('codigo_cliente'));
		$id_contrato = $file->get('id_contrato');
		return "/{$codigo_cliente}/contrato_{$id_contrato}/{$file_name}.{$file_ext}";
	}

	private function getExtension(&$file_fields, $mime_type, $content) {
		$file_ext = MimeTypes::getExtension($mime_type);
		if (empty($file_ext)) {
			$file_fields['data_tipo'] = $this->getMymeTypeFromContent($content);
			$file_ext = MimeTypes::getExtension($file_fields['data_tipo']);
		}
		return $file_ext;
	}

	private function S3() {
		if (empty($this->S3)) {
			$this->S3 = new S3(S3_UPLOAD_BUCKET);
		}
		return $this->S3;
	}

	private function putContentS3($name, $body, $content_type) {
		$file_name = $this->fileExistsS3(SUBDOMAIN . $name);
		$response = $this->S3()->putFileContents($file_name, $body, array(
			'ACL' => 'public-read',
			'ContentType' => $content_type,
			'ContentDisposition' => 'attachment'
		));
		return empty($response['ObjectURL']) ? null : $response['ObjectURL'];
	}

	private function fileExistsS3($file_name, $increment = 0) {
		if (!$this->S3()->fileExists($file_name)) {
			return $file_name;
		}
		if (!empty($increment)) {
			$file_name = preg_replace('/(-\d+)?\./', "-{$increment}.", $file_name);
		}
		return $this->fileExistsS3($file_name, ++$increment);
	}

	private function getFiles() {
		$SearchingBusiness = new SearchingBusiness($this->Session);
		$SearchCriteria = new SearchCriteria('File');
		$SearchCriteria->grouped_by('File.id_archivo');
		$SearchCriteria->filter('archivo_s3')->restricted_by('is_null')->by_condition('OR');
		$SearchCriteria->filter('archivo_s3')->compare_with("''")->by_condition('OR');
		$SearchCriteria->related_with('Client')->on_property('id_contrato');
		$SearchCriteria->related_with('Contract')->on_property('id_contrato');
		$SearchCriteria->related_with('Matter')->joined_with('Contract')->on_property('id_contrato');
		return $SearchingBusiness->searchByCriteria($SearchCriteria, [
				'File.id_archivo',
				'File.id_contrato',
				'File.archivo_nombre',
				'File.data_tipo',
				'IFNULL(Client.codigo_cliente, Matter.codigo_cliente) AS codigo_cliente',
		]);
	}

	private function startTimer() {
		$this->timer = round(microtime(true));
	}

	private function checkTimeout() {
		$elapsed = (round(microtime(true)) - $this->timer);
		return $elapsed >= $this->max_time;
	}

	private function getMymeTypeFromContent($content) {
		if (empty($content)) {
			return;
		}
		$mime_type = $this->getMimeTypeFromOfficeFiles($content);
		if (!is_null($mime_type)) {
			return $mime_type;
		}
		$this->debug('Tipo de archivo no conocido.');
		return false;
	}

	private function getMimeTypeFromOfficeFiles($content) {
		$file_name = BACKUPDIR . '/' . uniqid(SUBDOMAIN, true) . '.zip';
		file_put_contents($file_name, $content);
		$ZipArchive = new ZipArchive();
		$ZipArchive->open($file_name);
		if (!$ZipArchive->numFiles) {
			$ZipArchive->close();
			return null;
		}
		$file = $ZipArchive->statIndex(3)['name'];
		$ZipArchive->close();
		unlink($file_name);
		return MimeTypes::fromOfficeFiles($file);
	}

}
