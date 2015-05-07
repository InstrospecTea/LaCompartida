<?php

/**
*
*/
class HtmlBuilder
{

	public $childs;
	private $attributes;
	private $plain_attributes;
	private $closure;
	private $tag;
	private $value;


	function __construct($tag = 'div'){
		$this->childs = array();
		$this->attributes = array();
		$this->plain_attributes = array();
		$this->closure = true;
		$this->tag = $tag;
		$this->value = null;
	}

	public function __toString()
	{
	    return $this->render();
	}

	public function add_child($child){
		$this->childs[] = $child;
		return $this;
	}

	public function add_attribute($key, $value){
		$this->attributes[$key] = $value;
		return $this;
	}

	public function add_plain_attribute($attribute) {
		$this->plain_attributes[] = $attribute;
	}

	public function set_closure($is_closed){
		$this->closure = $is_closed;
		return $this;
	}

	public function set_tag($tag){
		$this->tag = $tag;
		return $this;
	}

	public function set_html($value){
		$this->value = $value;
		return $this;
	}

	public function render(){
		$html = '';
		//añade el comienzo del tag
		$html .= '<'.$this->tag;
		//añade los atributos
		foreach ($this->attributes as $key => $value) {
			$html .= ' '.$key.'="'.$value.'"';
		}
		//añade los atributos de tipo plain
		foreach ($this->plain_attributes as $plain_attribute) {
			$html .= ' '.$plain_attribute;
		}
		//verifica si cierra, entonces puede tener valor e hijos.
		if ($this->closure) {
			$html .= '>';
			//Renderiza a los hijos
			$html .= implode('', $this->childs);
//			foreach ($this->childs as $child) {
//				$html .= $child->render();
//			}
			//Agrega el valor
			$html .= $this->value;
			//Cierra el tag
			$html .= '</'.$this->tag.'>';
		} else {
			$html .= ' />';
		}

		return $html;

	}

	public function debug() {
		print_r($this);
		exit;
	}
}