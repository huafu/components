<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html\VirtualDom;


/**
 * Class Text
 * @package Huafu\Html\VirtualDom
 *
 * @method static static create(string $text = '', int $ent_type = NULL, string $charset = NULL)
 */
class Text extends Node
{
  /** @var string */
  public $text;

  /**
   * @param string $text
   */
  protected function _construct( $text = '' )
  {
    parent::_construct();
    $this->text = $text;
  }


  /**
   * @param string $text
   * @return $this
   */
  public function append( $text )
  {
    $this->text .= $text;

    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  public function prepend( $text )
  {
    $this->text = '' . $text . $this->text;

    return $this;
  }

  public function __toString()
  {
    return htmlentities(
      '' . $this->text,
      $this->config_ent_type,
      $this->config_charset
    );
  }
}
