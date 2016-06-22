<?php
echo $this->Html->script(Conf::RootDir() . '/app/layers/assets/js/JsonToTable.js');
$i = 0;
echo $this->Html->tag('h2', 'Json');
echo $this->Html->div($data);
echo $this->Html->tag('h2', 'Table');
echo $this->Html->div('', array('id' => 'json_to_table'));
echo $this->Html->script_block("var data = $data;");
?>

<script type="text/javascript" defer="defer">
	var json_to_table = new window.JsonToTable();
	var html = json_to_table.render(data);
	jQuery('#json_to_table').html(html);
</script>