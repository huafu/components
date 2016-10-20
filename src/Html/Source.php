<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Components\Html;


/**
 * Class Source
 * @package Huafu\Components\Html
 *
 * @method static static create(mixed $source = '')
 */
class Source extends Node
{

  /** @var string */
  public $source = '';

  /**
   * @param string $source
   */
  protected function _construct( $source = '' )
  {
    parent::_construct();
    $this->source = $source;
  }

  /**
   * @param string $source
   * @return $this
   */
  public function append_html( $source )
  {
    $this->source .= $source;

    return $this;
  }

  /**
   * @param string $source
   * @return $this
   */
  public function prepend_html( $source )
  {
    $this->source = $source . $this->source;

    return $this;
  }

  /**
   * @param string $text
   * @param int $quotes
   * @param null $charset
   * @return $this
   */
  public function append_text( $text, $quotes = NULL, $charset = NULL )
  {
    $this->source .= htmlentities(
      $text,
      $quotes === NULL ? self::$default_ent_type : $quotes,
      $charset === NULL ? self::$default_charset : $charset
    );

    return $this;
  }

  /**
   * @param string $text
   * @param int $quotes
   * @param null $charset
   * @return $this
   */
  public function prepend_text( $text, $quotes = NULL, $charset = NULL )
  {
    $this->source = htmlentities(
        $text,
        $quotes === NULL ? self::$default_ent_type : $quotes,
        $charset === NULL ? self::$default_charset : $charset
      )
      . $this->source;

    return $this;
  }

  public function __toString()
  {
    return '' . $this->source;
  }
}
