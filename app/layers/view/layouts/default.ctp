<?php

$Pagina = new Pagina($this->Session);
$Pagina->titulo = $title_for_layout;
$Pagina->PrintTop($this->params['popup']);

echo $this->element('Flash/flash_notice');

echo $content_for_layout;

$Pagina->PrintBottom();
