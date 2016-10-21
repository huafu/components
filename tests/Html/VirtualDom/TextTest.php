<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Html\VirtualDom;


use Huafu\Html\VirtualDom\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
  public function testCanCreate()
  {
    $node = Text::create();
    $this->assertSame('', $node->text, 'text property should be an empty string');
    $node = Text::create('hello');
    $this->assertSame('hello', $node->text, 'source property should be correct');
  }


  public function testCanAppendOrPrepend()
  {
    $node = Text::create('a>');

    $this->assertSame($node, $node->append('<b'), 'calling append should return itself');
    $this->assertSame('a><b', $node->text, 'calling append should append given string');

    $this->assertSame($node, $node->prepend('&c'), 'calling prepend should return itself');
    $this->assertSame('&ca><b', $node->text, 'calling prepend should prepend given string');
  }


  public function testToString()
  {
    $node = Text::create('&a>b<c');

    $this->assertSame('&amp;a&gt;b&lt;c', '' . $node, 'transforming the node into a string should return its text encoded');

    $node->text = NULL;
    $this->assertSame('', $node->__toString(), 'calling __toString() on a node with null text should return an empty string');
  }
}
