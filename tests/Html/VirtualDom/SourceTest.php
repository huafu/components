<?php

use Huafu\Html\VirtualDom\Source;
use PHPUnit\Framework\TestCase;

/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */
class SourceTest extends TestCase
{
  public function testCanCreate()
  {
    $node = Source::create();
    $this->assertSame('', $node->source, 'source property should be an empty string');
    $node = Source::create('hello');
    $this->assertSame('hello', $node->source, 'source property should be correct');
  }


  public function testCanAppendOrPrependHtml()
  {
    $node = Source::create('<div>');

    $this->assertSame($node, $node->append_html('</div>'), 'calling append_html should return itself');
    $this->assertSame('<div></div>', $node->source, 'calling append_html should append given string');

    $this->assertSame($node, $node->prepend_html('<img>'), 'calling prepend_html should return itself');
    $this->assertSame('<img><div></div>', $node->source, 'calling prepend_html should prepend given string');
  }


  public function testCanAppendOrPrependText()
  {
    $node = Source::create('<div>');

    $this->assertSame($node, $node->append_text('&'), 'calling append_text should return itself');
    $this->assertSame('<div>&amp;', $node->source, 'calling append_text should append given string encoded');

    $this->assertSame($node, $node->prepend_text('×'), 'calling prepend_text should return itself');
    $this->assertSame('&times;<div>&amp;', $node->source, 'calling prepend_text should prepend given string encoded');
  }


  public function testToString()
  {
    $node = Source::create('&times;<div>&amp');

    $this->assertSame('&times;<div>&amp', '' . $node, 'transforming the node into a string should return its source');

    $node->source = NULL;
    $this->assertSame('', $node->__toString(), 'calling __toString() on a node with null source should return an empty string');
  }
}
