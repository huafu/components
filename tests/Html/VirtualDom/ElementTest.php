<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-21
 */

namespace Html\VirtualDom;


use Huafu\Html\VirtualDom\Element;
use Huafu\Html\VirtualDom\Source;
use Huafu\Html\VirtualDom\Text;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
  public function testCanCreate()
  {
    $node = Element::create();
    $this->assertSame('div', $node->tag, 'tag property should have correct default value');
    $this->assertAttributeEquals(array(), '_children', $node, '_children property should have correct default value');

    $node = Element::create('img');
    $this->assertSame('img', $node->tag, 'tag property should be the one given in constructor');
    $this->assertAttributeSame(NULL, '_children', $node, '_children property should have correct default value for lonely tags');

    $node = Element::create(NULL, array('me' => 'you'));
    $this->assertAttributeEquals(
      array('me' => 'you'),
      '_attributes',
      $node,
      'attributes should be set correctly'
    );

    $node     = Element::create(NULL, NULL, 'some text');
    $children = static::readAttribute($node, '_children');
    $this->assertCount(1, $children, 'children array should contain one and only one child');
    $this->assertInstanceOf('Huafu\Html\VirtualDom\Text', $children[0], 'first child should be a text node');
    $this->assertSame('some text', $children[0]->text, 'first child content should be correct');
  }


  public function canSetHtmlContentDataProvider()
  {
    return array(
      array(NULL, 0),
      array('hello', 1, Source::create('hello')),
      array(array(Text::create('hello'), '<div>'), 2, Text::create('hello'), Source::create('<div>')),
    );
  }

  /**
   * @dataProvider canSetHtmlContentDataProvider
   */
  public function testCanSetHtmlContent( $content = NULL, $count = 0, $first = NULL, $second = NULL )
  {
    $node = Element::create();
    $this->assertSame($node, $node->set_html_content($content), 'set_html_content should return correct value');
    $this->assertAttributeCount($count, '_children', $node, 'children count should be correct');
    $children = static::readAttribute($node, '_children');
    if ( $count > 0 ) $this->assertEquals($first, $children[0], 'first child should be correct');
    if ( $count > 1 ) $this->assertEquals($second, $children[1], 'second child should be correct');
  }
}
