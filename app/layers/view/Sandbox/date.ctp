<?php

foreach ($langs as $lg) {
	$attrs = array(
		'class' => 'btn',
		'href' => Conf::RootDir() . "/app/Sandbox/date/$lg"
	);
	if ($lang == $lg) {
		$attrs['class'] .= ' btn-success';
	}
	echo $this->Form->button($lg, $attrs);
}

$dt = Date::parse('2016-01-01');
$tds = array(
	$this->Html->th('Format'),
	$this->Html->th("Fecha $lang")
);
$trs = array($this->Html->tr(implode('', $tds)));
for ($x = 1; $x <= 12; ++$x) {
	foreach ($formats as $format) {
		$tds = array(
			$this->Html->td($format, array('style' => 'text-align: left')),
			$this->Html->td($dt->format($format), array('style' => 'text-align: left'))
		);
		$trs[] = $this->Html->tr(implode('', $tds));
	}
	$dt->addMonth();
}
echo $this->Html->table(implode('', $trs), array('class' => 'table', 'width' => '50%'));
