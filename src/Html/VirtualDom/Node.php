<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html\VirtualDom;

/**
 * Class Node
 * @package Huafu\Html\VirtualDom
 */
abstract class Node
{
  /** @var string */
  public static $default_charset = 'UTF-8';
  /** @var int */
  public static $default_ent_type = ENT_COMPAT;

  abstract public function __toString();

  final protected function __construct()
  {
  }

  protected function _construct()
  {
  }

  /**
   * @return static
   */
  static public function create()
  {
    return self::_create(func_get_args(), get_called_class());
  }

  /**
   * @param array $args
   * @param null $class
   * @return static
   */
  static protected function _create( array $args = NULL, $class = NULL )
  {
    if ( !$args ) $args = array();
    if ( !$class ) $class = get_called_class();
    $node = new $class;
    call_user_func_array(array($node, '_construct'), $args);

    return $node;
  }

  /**
   * @param array $args
   * @param string $self
   * @return string
   */
  static public function _class_for_args( array $args, $self )
  {
    return $self;
  }
}
