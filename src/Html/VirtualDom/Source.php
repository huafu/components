<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html\VirtualDom;


/**
 * Class Source
 * @package Huafu\Html\VirtualDom
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
      $quotes === NULL ? $this->config_ent_type : $quotes,
      $charset === NULL ? $this->config_charset : $charset
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
        $quotes === NULL ? $this->config_ent_type : $quotes,
        $charset === NULL ? $this->config_charset : $charset
      )
      . $this->source;

    return $this;
  }

  public function __toString()
  {
    return '' . $this->source;
  }
}
