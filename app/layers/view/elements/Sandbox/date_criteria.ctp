<?php
echo $this->Html->h1('Prueba de Criteria (date)');
$code = <<<PHP
\$fecha = Date::parse(\$date)->toDate();
\$Criteria = new Criteria();
\$Criteria->add_select('id_trabajo')
	->add_select('duracion')
	->add_from('trabajo')
	->add_restriction(CriteriaRestriction::equals('fecha', "'\$fecha'"));
PHP;
echo $this->Html->h3('Code');
echo $this->Html->pre($code, $attrs);
echo $this->Html->h3('Result $query');
eval($code);
echo $this->Html->pre($Criteria, $attrs);
