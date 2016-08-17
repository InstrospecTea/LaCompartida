<?php
echo $this->Html->h1('Prueba de query (date time)');
$code = <<<PHP
\$fecha = Date::parse(\$date);
\$query = "SELECT id_trabajo, duracion FROM trabajo WHERE fecha_creacion >= '\$fecha'";
PHP;
echo $this->Html->h3('Code');
echo $this->Html->pre($code, $attrs);
echo $this->Html->h3('Result $query');
eval($code);
echo $this->Html->pre($query, $attrs);
