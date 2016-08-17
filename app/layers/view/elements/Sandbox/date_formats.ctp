<?php
echo $this->Html->h1('Prueba de formatos');
$dt = Date::parse($date);
$tds = array(
	$this->Html->th('Format'),
	$this->Html->th("Fecha $lang")
);
$trs = array($this->Html->tr(implode('', $tds)));
foreach ($formats as $key => $format) {
	$tds = array(
		$this->Html->td($format, array('style' => 'text-align: left')),
		$this->Html->td($dt->format($format), array('style' => 'text-align: left'))
	);
	$trs[] = $this->Html->tr(implode('', $tds));
}

echo $this->Html->table(implode('', $trs), array('class' => 'table', 'width' => '50%'));
