<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html\VirtualDom;

use Huafu\Lang\Configurable;

/**
 * Class Node
 * @package Huafu\Html\VirtualDom
 *
 * @property string $config_charset = 'UTF-8'
 * @property string $config_ent_type = ENT_COMPAT
 * @method static string config_charset($default = NULL)
 * @method static int config_ent_type($default = NULL)
 */
abstract class Node
{
  use Configurable;

  protected static $_default_class_config = array(
    'charset'  => 'UTF-8',
    'ent_type' => ENT_COMPAT,
  );

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
}
