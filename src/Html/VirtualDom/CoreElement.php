<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html\VirtualDom;


/**
 * Class CoreElement
 * @package Huafu\Html\VirtualDom
 *
 * @method static static create(null|string $tag = NULL, null|string|array $attributes = NULL, null|mixed $content = NULL)
 *
 * @method static string[] config_lonely_tags($default = NULL)
 * @method static string[] config_text_object_classes($default = NULL)
 * @method static callable config_string_is_html_callback($default = NULL)
 *
 * @property string[] config_lonely_tags = array('img', 'hr', 'link')
 * @property string[] config_text_object_classes = array()
 * @property callable config_string_is_html_callback = null
 */
abstract class CoreElement extends Node
{
  static protected $_default_class_config = array(
    'lonely_tags'             => array('img', 'hr', 'link'),
    'string_is_html_callback' => NULL,
    'text_object_classes'     => array(),
  );

  /** @var string */
  public $tag = 'div';
  /** @var array array */
  public $attributes = array();

  /**
   * @param null|string $tag
   * @param null|string|array $attributes
   */
  protected function _construct( $tag = NULL, $attributes = NULL )
  {
    parent::_construct();
    if ( $tag ) $this->tag = $tag;
    if ( $attributes ) $this->attributes = self::merge_attributes($attributes);
  }

  /**
   * @param string $class
   * @return $this
   */
  public function add_class( $class )
  {
    return $this->set_attribute('class', self::merge_classes($this->get_attribute('class'), $class));
  }

  /**
   * @param string|string[] $class
   * @return $this
   */
  public function remove_class( $class )
  {
    $to_remove = array_unique(is_array($class) ? $class : ($class ? explode(' ', $class) : array()), SORT_REGULAR);
    if ( ($classes = $this->get_attribute('class')) )
    {
      $classes = explode(' ', $classes);
      foreach ( $to_remove as $class )
      {
        if ( ($k = array_search($class, $classes, TRUE)) !== FALSE )
        {
          array_splice($classes, $k, 1);
        }
      }
      $this->attributes['class'] = $classes;
    }

    return $this;
  }

  /**
   * @param string $class
   * @return bool
   */
  public function has_class( $class )
  {
    return ($classes = $this->get_attribute('class')) && in_array($class, explode(' ', $classes), TRUE);
  }

  /**
   * @param null|string $name
   * @param null|mixed $value
   * @return $this
   */
  public function set_attribute( $name = NULL, $value = NULL )
  {
    $argc = func_num_args();
    if ( $argc === 1 && is_array($name) )
    {
      $this->attributes = self::merge_attributes($this->attributes, $name);

      return $this;
    }
    if ( $value === NULL )
    {
      $this->unset_attribute($name);
    }
    else
    {
      $this->attributes[$name] = $value;
    }

    return $this;
  }

  /**
   * @param null|string $name
   * @return $this
   */
  public function unset_attribute( $name = NULL )
  {
    if ( $name === NULL )
    {
      $this->attributes = array();
    }
    else
    {
      unset($this->attributes[$name]);
    }

    return $this;
  }

  /**
   * @param null|string $name
   * @return array|mixed|null
   */
  public function get_attribute( $name = NULL )
  {
    if ( $name === NULL )
    {
      return $this->attributes;
    }

    return isset($this->attributes[$name]) ? $this->attributes[$name] : NULL;
  }

  /**
   * @param null|array|string $extra_attributes
   * @return string
   */
  public function open_tag( $extra_attributes = NULL )
  {
    $out   = '<' . $this->tag;
    $attrs = self::merge_attributes($this->attributes, $extra_attributes);
    foreach ( $attrs as $key => $value )
    {
      if ( $value === NULL ) continue;
      if ( is_object($value) && in_array(get_class($value), $this->config_text_object_classes, TRUE) ) $value = '' . $value;
      // we need to json_encode the value if it's a data attribute and its value is not scalar
      if ( substr($key, 0, 5) === 'data-' && (!is_scalar($value) || is_bool($value)) )
      {
        $value = json_encode($value);
      }
      $out .= ' ' . $key . '="' . htmlentities($value, $this->config_ent_type, $this->config_charset) . '"';
    }
    $out .= '>';

    return $out;
  }

  /**
   * @return string
   */
  abstract public function get_html_content();

  /**
   * @return string
   */
  public function close_tag()
  {
    return '</' . $this->tag . '>';
  }

  /**
   * @param string $one
   * @param string ...$other
   * @return string
   */
  static public function merge_classes( $one = NULL, $other = NULL )
  {
    if ( func_num_args() < 1 ) return '';
    if ( func_num_args() === 1 && is_array($one) )
    {
      return call_user_func_array(array(__CLASS__, __FUNCTION__), $one);
    }
    $all = explode(' ', implode(' ', func_get_args()));
    // we can't use array_unique() as it is sorting and we do not want to sort
    $res = array();
    foreach ( $all as $i => $class )
    {
      if ( empty($class) ) continue;
      if ( substr($class, 0, 1) === '!' )
      {
        unset($res[$class]);
      }
      else
      {
        $res[$class] = $i;
      }
    }
    // so that we keep the latest added at the end
    asort($res, SORT_REGULAR);

    return implode(' ', array_keys($res));
  }


  /**
   * @param array|string $array1
   * @param array|string ...$array2
   * @return array
   */
  static public function merge_attributes( $array1, $array2 = NULL )
  {
    $res = $array1 === NULL ? array() : $array1;
    if ( is_string($res) ) $res = array('class' => $res);
    $args    = array_slice(func_get_args(), 1);
    $classes = array_key_exists('class', $res)
      ? (is_array($res['class']) ? $res['class'] : array($res['class']))
      : array();
    foreach ( $args as $array )
    {
      if ( $array !== NULL )
      {
        if ( is_string($array) ) $array = array('class' => $array);
        if ( array_key_exists('class', $array) )
        {
          $class     = $array['class'];
          $classes[] = is_array($class) ? implode(' ', $class) : $class;
          unset($array['class']);
        }
        $res = array_merge($res, $array);
      }
    }
    $classes = self::merge_classes($classes);
    if ( $classes != '' )
    {
      $res['class'] = $classes;
    }
    else
    {
      unset($res['class']);
    }

    return $res;
  }


  public function __toString()
  {
    $out = $this->open_tag();
    if ( ($html = $this->get_html_content()) !== NULL )
    {
      $out .= $html . $this->close_tag();
    }

    return $out;
  }
}
