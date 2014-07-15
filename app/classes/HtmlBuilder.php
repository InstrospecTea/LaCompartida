<?php

/**
* 		
*/
class HtmlBuilder 
{
	
	public $childs;
	private $attributes;
	private $closure;
	private $tag;
	private $value;


	function __construct(){
		$this->childs = array();
		$this->attributes = array();
		$this->closure = true;
		$this->tag = 'div';
		$this->value = null;
	}

	public function __toString()
	{
	    return $this->render();
	}

	public function add_child(HtmlBuilder $child){
		$this->childs[] = $child;
		return $this;
	}

	public function add_attribute($key, $value){
		$this->attributes[$key] = $value;
		return $this;
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
		//verifica si cierra, entonces puede tener valor e hijos.
		if ($this->closure) {
			$html .= '>';
			//Renderiza a los hijos
			foreach ($this->childs as $child) {
				$html .= $child->render();
			}
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