<?php
namespace TTB;
require_once dirname(__FILE__) . '/../conf.php';
use \Conf;
require_once Conf::ServerDir().'/../fw/classes/Html.php';


class Html extends \Html {

/**
  * Construye un tag html
*/
  public static function tag($tag = 'div', $content = '', $attributes = null, $closed = false) {
    $html = '';


    $attributes = is_array($attributes) ? self::attributes($attributes) : $attributes;

    if ($closed) {
      $html = sprintf('<%s%s />', $tag, $attributes);
    } else {
      $html = sprintf('<%s%s>%s</%s>', $tag, $attributes, $content, $tag);
    }

    return $html;
  }

  public function attributes($attributes) {
    $html = '';
    if (is_array($attributes)) {
      foreach ($attributes as $name => $value) {
        $html .= sprintf(' %s="%s"', $name, $value);
      }
    } else {
      $html = $attributes;
    }
    return $html;
  }

  public function img($image, $attributes) {
	  $attr = array_merge(array('src' => $image), $attributes);
	  return $this->tag('img', '', $attr, true);
  }

}
