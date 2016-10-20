<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Components\Html;


/**
 * Class Text
 * @package Huafu\Components\Html
 *
 * @method static static create(string $text = '', int $ent_type = NULL, string $charset = NULL)
 */
class Text extends Node
{
  /** @var string */
  public $text;
  /** @var int */
  public $ent_type;
  /** @var string */
  public $charset;

  /**
   * @param string $source
   * @param null|int $ent_type
   * @param null|string $charset
   */
  protected function _construct( $source, $ent_type = NULL, $charset = NULL )
  {
    parent::_construct();
    $this->text     = $source;
    $this->ent_type = $ent_type;
    $this->charset  = $charset;
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
      $this->ent_type === NULL ? self::$default_ent_type : $this->ent_type,
      $this->charset === NULL ? self::$default_charset : $this->charset);
  }
}
