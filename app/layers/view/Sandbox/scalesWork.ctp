<?php
  $container = new HtmlBuilder();
  $container->set_tag('div');

  function constructTableHead($scale) {
    //Table header
    $thead = new HtmlBuilder();
    $thead->set_tag('thead');
    //Table header row
    $tr = new HtmlBuilder();
    $tr->set_tag('tr');
    //Table headers columns
    $th_date = new HtmlBuilder('th');
    $th_date->set_html(__('Fecha'));
    $th_user = new HtmlBuilder('th');
    $th_user->set_html(__('Profesional'));
    $th_description = new HtmlBuilder('th');
    $th_description->set_html(__('Descripción'));
    $th_workedTime = new HtmlBuilder('th');
    $th_workedTime->set_html(__('Tiempo trabajado'));
    $th_usedTime = new HtmlBuilder('th');
    $th_usedTime->set_html(__('Tiempo utilizado').('(min)'));
    $th_value = new HtmlBuilder('th');
    $th_value->set_html(__('Valor'));
    $tr
      ->add_child($th_date)
      ->add_child($th_user)
      ->add_child($th_description)
      ->add_child($th_workedTime)
      ->add_child($th_usedTime);
    if ($scale->get('fixedAmount') == 0) {
      $tr->add_child($th_value);
    }
    return $thead->add_child($tr);
  }

  function constructTableBody($scale, $language, $currency) {
    //Table body
    $tbody = new HtmlBuilder();
    $tbody->set_tag('tbody');
    $totalmins = 0;
    foreach($scale->get('scaleWorks') as $work) {
      //One table body row for every work
      $tr = new HtmlBuilder();
      $tr->set_tag('tr');
      $td_date = new HtmlBuilder('td');
      $td_date->set_html($work->get('fecha'));
      $td_user = new HtmlBuilder('td');
      $td_user->set_html($work->get('apellido1'));
      $td_description = new HtmlBuilder('td');
      $td_description->set_html($work->get('descripcion'));
      $td_workedTime = new HtmlBuilder('td');
      $td_workedTime->set_html($work->get('duracion_cobrada'));
      $td_usedTime = new HtmlBuilder('td');
      $td_usedTime->set_html($work->get('usedTime'));
      $totalmins += $work->get('usedTime');
      $td_value = new HtmlBuilder('td');
      $formatted = number_format($work->get('actual_amount'),
        $currency->get('cifras_decimales'),
        $language->get('separador_decimales'),
        $language->get('separador_miles')
      );
      $td_value->set_html($formatted);
      $tr
        ->add_child($td_date)
        ->add_child($td_user)
        ->add_child($td_description)
        ->add_child($td_workedTime)
        ->add_child($td_usedTime);
      if ($scale->get('fixedAmount') == 0) {
        $tr->add_child($td_value);
      }
      $tbody->add_child($tr);
    }
    //Final row
    if ($scale->get('fixedAmount') == 0) {
      $index = 5;
    } else {
      $index = 4;
    }
    $tr = new HtmlBuilder('tr');
    $td_label = new HtmlBuilder('th');
    $td_label->set_html('Tiempo total (mins):');
    $td_label->add_attribute('colspan', $index - 1);
    $td_value = new HtmlBuilder('th');
    $td_value->set_html($totalmins);
    $td_value->add_attribute('colspan', $index);
    $tr->add_child($td_label);
    $tr->add_child($td_value);
    $tbody->add_child($tr);

    $tr = new HtmlBuilder('tr');
    $td_label = new HtmlBuilder('th');
    $td_label->set_html('Total:');
    $td_label->add_attribute('colspan', $index - 1);
    $td_value = new HtmlBuilder('th');
    $formatted = number_format($scale->get('amount'),
      $currency->get('cifras_decimales'),
      $language->get('separador_decimales'),
      $language->get('separador_miles')
    );
    $td_value->set_html($formatted);
    $td_value->add_attribute('colspan', $index);
    $tr->add_child($td_label);
    $tr->add_child($td_value);
    $tbody->add_child($tr);

    if ($scale->get('discountRate') != 0) {
      $tr = new HtmlBuilder('tr');
      $td_label = new HtmlBuilder('th');
      $td_label->set_html('Descuento ('.$scale->get('discountRate').'%):');
      $td_label->add_attribute('colspan', $index - 1);
      $td_value = new HtmlBuilder('th');
      $formatted = number_format($scale->get('discount'),
        $currency->get('cifras_decimales'),
        $language->get('separador_decimales'),
        $language->get('separador_miles')
      );
      $td_value->set_html($formatted);
      $td_value->add_attribute('colspan', $index);
      $tr->add_child($td_label);
      $tr->add_child($td_value);
      $tbody->add_child($tr);

      $tr = new HtmlBuilder('tr');
      $td_label = new HtmlBuilder('th');
      $td_label->set_html('Total descontado:');
      $td_label->add_attribute('colspan', $index - 1);
      $td_value = new HtmlBuilder('th');
      $formatted = number_format($scale->get('netAmount'),
        $currency->get('cifras_decimales'),
        $language->get('separador_decimales'),
        $language->get('separador_miles')
      );
      $td_value->set_html($formatted);
      $td_value->add_attribute('colspan', $index);
      $tr->add_child($td_label);
      $tr->add_child($td_value);
      $tbody->add_child($tr);
    }

    return $tbody;
  }

  foreach($slidingScales as $scale) {
    if ($scale->get('amount') != 0) {
      $title = new HtmlBuilder();
      $title->set_tag('h3');
      $title->set_html('Escalón #'. $scale->get('scale_number'));
      $container->add_child($title);
      //Construct table
      $table = new HtmlBuilder();
      $table->set_tag('table');
      $table->add_child(constructTableHead($scale));
      $table->add_child(constructTableBody($scale, $language, $currency));
      $container->add_child($table);
    }
  }

  echo $container->render();
?>