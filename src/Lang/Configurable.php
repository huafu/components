<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-22
 */

namespace Huafu\Lang;


/**
 * Class Configurable
 * @package Huafu\Lang
 */
trait Configurable
{
  ///** @var array */
  //protected static $_default_class_config = NULL;
  /** @var array[] */
  private static $_class_configs = array();
  /** @var bool[] */
  private static $_class_configured = array();
  /** @var null|array */
  private $_object_config = NULL;

  /**
   * @param array $config
   * @return bool
   */
  public static function configure( array $config = array() )
  {
    return static::_configure($config, get_called_class());
  }

  /**
   * @param array $config
   * @param null|string $class
   * @return bool
   */
  protected static function _configure( array $config = array(), $class = NULL )
  {
    if ( !$class ) $class = get_called_class();

    // make sure we have our array set
    self::_config_chain($class);

    $configured = isset(self::$_class_configured[$class]);

    self::$_class_configured[$class] = TRUE;

    // using a loop to keep references to our array in the chain
    $source = &self::$_class_configs[$class];
    foreach ( $config as $key => $value )
    {
      $source[$key] = $value;
    }

    return $configured;
  }

  /**
   * @param string $class
   * @return array[]
   */
  private static function _config_chain( $class )
  {
    static $cache = [];
    if ( isset($cache[$class]) ) return $cache[$class];

    $classes = [$class];
    $c       = $class;
    while ( ($c = get_parent_class($c)) )
    {
      $classes[] = $c;
    }

    $cache[$class] = array();
    foreach ( $classes as $C )
    {
      if ( !isset(self::$_class_configs[$C]) )
      {
        self::$_class_configs[$C] =
          (
            isset($C::$_default_class_config)
            && (new \ReflectionClass($C))->getProperty('_default_class_config')
                                         ->getDeclaringClass()->name === $C
          )
            ? $C::$_default_class_config
            : array();
      }
      $cache[$class][] = &self::$_class_configs[$C];
    }

    return $cache[$class];
  }


  /**
   * @param array $local
   * @param string $class
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  private static function _get_config( $local, $class, $key, $default = NULL )
  {

    if ( isset($local) && isset($local[$key]) )
    {
      return $local[$key];
    }
    else
    {
      $chain = self::_config_chain($class);
      foreach ( $chain as $source )
      {
        if ( isset($source[$key]) ) return $source[$key];
      }
    }

    return $default;
  }

  /**
   * @param array $local
   * @param string $class
   * @param string $key
   * @param mixed $value
   */
  private static function _set_config( &$local, $class, $key, $value = NULL )
  {
    if ( isset($local) && is_array($local) )
    {
      $local[$key] = $value;
    }
    else
    {
      if ( !isset(self::$_class_configs[$class]) ) self::$_class_configs[$class] = array();
      self::$_class_configs[$class][$key] = $value;
    }
  }


  function __call( $name, $arguments )
  {
    if ( !isset($this) || !($this instanceof self) ) return static::__callStatic($name, $arguments);
    if ( substr($name, 0, 7) === 'config_' )
    {
      $val_or_default = array_shift($arguments);
      $is_set         = (bool)array_shift($arguments);
      $key            = substr($name, 7);

      return $is_set
        ? self::_set_config($this->_object_config, get_called_class(), $key, $val_or_default)
        : self::_get_config($this->_object_config, get_called_class(), $key, $val_or_default);
    }

    if ( ($class = get_parent_class()) && method_exists($class, __FUNCTION__) )
    {
      return parent::__call($name, $arguments);
    }
    throw new \BadMethodCallException('Class ' . get_called_class() . ' has no method ' . $name . '.');
  }


  function __set( $name, $value )
  {
    if ( substr($name, 0, 7) === 'config_' )
    {
      return self::_set_config($this->_object_config, get_class($this), substr($name, 7), $value);
    }

    if ( ($class = get_parent_class()) && method_exists($class, __FUNCTION__) )
    {
      return parent::__set($name, $value);
    }
    throw new \Exception('Class ' . get_called_class() . ' has no property ' . $name . '.');
  }


  function __get( $name )
  {
    if ( substr($name, 0, 7) === 'config_' )
    {
      return self::_get_config($this->_object_config, get_class($this), substr($name, 7));
    }

    if ( ($class = get_parent_class()) && method_exists($class, __FUNCTION__) )
    {
      return parent::__get($name);
    }
    throw new \Exception('Class ' . get_called_class() . ' has no property ' . $name . '.');
  }


  static function __callStatic( $name, $arguments )
  {
    if ( substr($name, 0, 7) === 'config_' )
    {
      $val_or_default = array_shift($arguments);
      $is_set         = (bool)array_shift($arguments);
      $key            = substr($name, 7);
      $local          = NULL;

      return $is_set
        ? self::_set_config($local, get_called_class(), $key, $val_or_default)
        : self::_get_config($local, get_called_class(), $key, $val_or_default);
    }

    if ( ($class = get_parent_class()) && method_exists($class, __FUNCTION__) )
    {
      return parent::__callStatic($name, $arguments);
    }
    throw new \BadMethodCallException('Class ' . get_called_class() . ' has no method ' . $name . '.');
  }
}
