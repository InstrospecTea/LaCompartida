<style>
h1 {
	margin-top: 2em;
}

table.table td {
	text-align: left;
}

pre.php {
	text-align: left;;
	width: 70%;
	margin: 0 auto;
	border: 1px solid #ccc;
	border-left: 3px solid #ccc;
	overflow: auto;
	padding: 0.5em 1em;
}
</style>
<?php
$attrs = array('style' => 'padding: 5px;');
$this->Form->defaultLabel = false;
echo $this->Form->create('', array('method' => 'post'));
?>
<table class="table">
	<tr>
		<td><?= $this->Form->label(__('Language'), 'lang'); ?></td>
		<td><?= $this->Form->select('lang', $langs, $lang, array('empty' => false)); ?></td>
	</tr>
	<tr>
		<td><?= $this->Form->label(__('Add custom format'), 'custom'); ?></td>
		<td><?= $this->Form->input('custom', $custom); ?></td>
	</tr>
	<tr>
		<td><?= $this->Form->label(__('Date'), 'label'); ?></td>
		<td><?= $this->Form->input('date', $date, array('class' => 'fechadiff')); ?></td>
	</tr>
	<tr>
		<td></td>
		<td><?= $this->Form->submit('Enviar'); ?></td>
	</tr>
</table>
<?php
echo $this->Form->end();

echo $this->element('Sandbox/date_formats', compact('date', 'formats'));
$attrs = array('class' => 'php');
echo $this->element('Sandbox/date_query', compact('date', 'attrs'));
echo $this->element('Sandbox/date_criteria', compact('date', 'attrs'));
echo $this->Form->script();
