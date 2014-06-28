<?php
use Codeception\Util\Stub;

class HtmlBuilderTest extends BaseUnitTest
{
   /**
    * @var \CodeGuy
    */
    protected $codeGuy;
    protected $builder;

    protected function _before() {
      $this->setVerboseErrorHandler();
      $this->builder = new HtmlBuilder();
    }

    protected function _after() {
      $this->builder = new HtmlBuilder();
    }

    // tests
    public function testHtmlRenderingWithClosedTag() {
      $this->builder->set_tag('div');
      $this->builder->set_closure(true);
      $string = '<div></div>';
      $this->assertEquals($this->builder->render(), $string);
    }

    public function testHtmlRenderingWithUnclosedTag() {
      $this->builder->set_tag('input');
      $this->builder->set_closure(false);
      $string = '<input />';
      $this->assertEquals($this->builder->render(), $string);
    }

    public function testHtmlRenderingWithClosedTagAndAttributes() {
      $this->builder
          ->set_tag('div')
          ->set_closure(true)
          ->add_attribute('class','testClass')
          ->add_attribute('style','background-color: #123456;')
          ->add_attribute('id','testBox');

      $string = '<div class="testClass" style="background-color: #123456;" id="testBox"></div>';
      $this->assertEquals($this->builder->render(), $string);
    }

    public function testHtmlRenderingWithUnclosedTagAndAttributes() {
      $this->builder
          ->set_tag('input')
          ->set_closure(false)
          ->add_attribute('class','testClass')
          ->add_attribute('style','background-color: #123456;')
          ->add_attribute('id','testInput')
          ->add_attribute('value',"testValue");

      $string = '<input class="testClass" style="background-color: #123456;" id="testInput" value="testValue" />';
      $this->assertEquals($this->builder->render(), $string);
    }

    public function testHtmlRenderingWithClosedTagAttributesAndChildren() {

      $children = new HtmlBuilder();
      $children
          ->set_tag('p')
          ->set_closure(true)
          ->set_html('hola!');

      $this->builder
          ->set_tag('div')
          ->set_closure(true)
          ->add_attribute('class','testClass')
          ->add_attribute('style','background-color: #123456;')
          ->add_attribute('id','testBox')
          ->add_child($children);

      $string = '<div class="testClass" style="background-color: #123456;" id="testBox"><p>hola!</p></div>';
      $this->assertEquals($this->builder->render(), $string);
    }

    public function testHtmlRenderingWithUnclosedTagAttributesAndChildrenThatShouldNotBeAppended() {

      $children = new HtmlBuilder();
      $children
          ->set_tag('p')
          ->set_closure(true)
          ->set_html('hola!');

      $this->builder
          ->set_tag('input')
          ->set_closure(false)
          ->add_attribute('class','testClass')
          ->add_attribute('style','background-color: #123456;')
          ->add_attribute('id','testInput')
          ->add_attribute('value',"testValue")
          ->add_child($children);

      $string = '<input class="testClass" style="background-color: #123456;" id="testInput" value="testValue" />';
      $this->assertEquals($this->builder->render(), $string);
    }

}
