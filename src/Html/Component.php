<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Html;


use Huafu\Exception;
use Huafu\Html\VirtualDom\CoreElement;

/**
 * Class Component
 * @package Huafu\Html
 *
 * @method static string config_root_namespace($default = NULL, $set_config = FALSE)
 * @method static string config_root_path($default = NULL, $set_config = FALSE)
 * @method static string config_base_uri($default = NULL, $set_config = FALSE)
 *
 * @property string config_root_namespace = 'Components'
 * @property string config_root_path = 'components'
 * @property string config_base_uri = 'c'
 */
abstract class Component extends CoreElement
{
  /** @var null|Component */
  static private $_standalone_instance = NULL;
  /** @var Component[] */
  static private $_instances = array();
  /** @var callable */
  static private $_include_resource = NULL;

  /** @var array */
  static protected $_default_class_config = array(
    'root_namespace' => 'Components',
    'root_path'      => 'components',
    'base_uri'       => 'c',
    'debug_mode'     => FALSE,
  );

  /** @var string[] */
  static protected $_merged_data_names = array('attributes');
  /** @var bool */
  static protected $_is_public = NULL;
  /** @var string[] */
  static protected $_input_config = array();
  /** @var string[] */
  static protected $_used_components = array();

  /**
   * @var bool
   */
  private $_is_auto_id = NULL;

  /**
   * @param array|NULL $data
   */
  protected final function _construct( $data = NULL, $_dummy = NULL )
  {
    parent::_construct(NULL, array('class' => 'component', 'data-component' => static::component_name()));

    $merged = static::_merged_data();
    // inject merged props first
    foreach ( $merged as $key => $val )
    {
      $this->{$key} = $val;
    }

    if ( $data )
    {
      // then others
      foreach ( $data as $key => $value )
      {
        if ( substr($key, 0, 1) === '_' ) continue;

        if ( in_array($key, $merged, TRUE) )
        {
          $this->{$key} = self::_merge($key, $this->{$key}, $value ? $value : array());
        }
        else
        {
          $this->{$key} = $value;
        }
      }
    }
  }

  /**
   * Called on creation before checking the rights
   */
  public function setup()
  {
  }

  /**
   * Called on creation after checking the rights
   */
  public function initialize()
  {
  }

  /**
   * @return bool
   */
  public function check_rights()
  {
    return TRUE;
  }

  /**
   * @return null|string
   */
  public function template()
  {
    return static::default_template();
  }

  /**
   * @return null|string
   */
  public function layout()
  {
    return static::default_layout();
  }

  /**
   * @return null|string|string[]
   */
  public function stylesheets()
  {
    return static::default_stylesheets();
  }

  /**
   * @return null|string|string[]
   */
  public function javascripts()
  {
    return static::default_javascripts();
  }


  /**
   * @param array|NULL $data_overrides
   * @return null|string
   */
  public function current_uri( array $data_overrides = NULL )
  {
    $data = $this->extract_properties();
    if ( $data_overrides ) $data = array_merge($data, $data_overrides);

    return static::uri($data);
  }

  /**
   * @return string
   */
  public function render_content()
  {
    return ($file = $this->template())
      ? $this->_render($file)
      : '';
  }

  /**
   * @param string[]|NULL $names
   * @return array
   */
  public function extract_properties( array $names = NULL )
  {
    $values = get_object_vars($this);
    if ( $names !== NULL )
    {
      $values = array_intersect_key($values, array_combine($names, $names));
    }

    return $values;
  }


  /**
   * Include required resources if they're not yet included
   * @return string|null
   */
  public function include_resources()
  {
    $files = array();
    if ( ($resources = $this->stylesheets()) ) $files = array_merge($files, $resources);
    if ( ($resources = $this->javascripts()) ) $files = array_merge($files, $resources);

    return self::_include_resource($files);
  }

  /**
   * @param null $suffix
   * @return string
   */
  public function css_selector( $suffix = NULL )
  {
    return self::_css_escape($this->get_id($suffix));
  }

  /**
   * @param null|string $suffix
   * @return string
   */
  public function get_id( $suffix = NULL )
  {
    $id = $this->set_attribute('id');
    if ( !$id ) $id = $this->set_id()->get_attribute('id');

    return $id . ($suffix ? '.' . $suffix : '');
  }

  /**
   * @param null|string $suffix
   * @return $this
   */
  public function set_id( $suffix = NULL )
  {
    $this->_is_auto_id = !$suffix;

    return $this->set_attribute('id', static::generate_id($suffix));
  }


  /**
   * @return bool
   */
  public function is_standalone_instance()
  {
    return $this === self::$_standalone_instance;
  }

  /**
   * @return bool
   */
  public function is_dedicated_instance()
  {
    return in_array($this, self::$_instances, TRUE);
  }

  /**
   * @param null|string $suffix
   * @return string
   */
  public function unique_form_name( $suffix = NULL )
  {
    return
      str_replace('.', '_', $this->_is_auto_id ? static::component_name() : $this->get_id())
      . ($suffix ? '_' . $suffix : '');
  }

  /**
   * @return string
   */
  static public function css_global_selector()
  {
    return '.component[data-component="' . self::_css_escape(static::component_name()) . '"]';
  }


  /**
   * @param array|NULL $data
   * @return static
   */
  static public function create( array $data = NULL )
  {
    return static::_create(array($data))->_setup();
  }

  /**
   * @param array $input
   * @return array
   */
  static public function parse_standalone_input( $input )
  {
    return static::_map_input_and_data($input, TRUE);
  }

  /**
   * @param array $data
   * @return array
   */
  static public function build_standalone_input( $data = NULL )
  {
    return static::_map_input_and_data($data, FALSE);
  }

  /**
   * @param string $data_name
   * @param mixed $value
   * @param array $config
   * @return mixed
   */
  static public function parse_input_value( $data_name, $value, $config )
  {
    if ( isset($config['type']) )
    {
      $type = $config['type'];

      if ( substr($type, -2) == '[]' )
      {
        $res = array();
        if ( !is_array($value) )
        {
          $value = array_filter(explode(',', '' . $value), function ( $v )
          {
            return !empty($v);
          });
        }
        $config['type'] = substr($config['type'], 0, -2);
        foreach ( $value as $val )
        {
          $res[] = static::parse_input_value($data_name, $val, $config);
        }

        return $res;
      }

      switch ( $type )
      {
        case 'bool':
          return (bool)$value;

        case 'int':
          return intval($value, 10);

        case 'float':
          return floatval($value);
      }
    }

    return $value;
  }

  /**
   * @param string $data_name
   * @param mixed $value
   * @param array $config
   * @return mixed
   */
  static public function build_input_value( $data_name, $value, $config )
  {
    if ( isset($config['type']) )
    {
      $type = $config['type'];

      if ( substr($type, -2) === '[]' )
      {
        $res = array();
        if ( !$value ) $value = array();
        $config['type'] = substr($config['type'], 0, -2);
        foreach ( $value as $val )
        {
          $res[] = static::build_input_value($data_name, $val, $config);
        }

        return implode(',', $res);
      }

      switch ( $type )
      {
        case 'bool':
          return $value ? '1' : NULL;

        case 'int':
        case 'float':
          return $value === NULL ? NULL : '' . $value;
      }
    }

    return $value;
  }


  /**
   * @param array|NULL $data
   * @param bool $data_is_input
   * @return null|string
   */
  static public function uri( array $data = NULL, $data_is_input = FALSE )
  {
    if ( !static::is_public() ) return NULL;
    $uri = substr(get_called_class(), strlen(self::config_root_namespace()) + 1);
    $uri = str_replace('_', '-', strtolower($uri));
    $uri = str_replace('\\', '/', $uri);
    if ( $data )
    {
      if ( $data_is_input )
      {
        $input = $data;
      }
      else
      {
        $input = static::build_standalone_input($data);
      }
      $uri .= '?' . http_build_query($input);
    }

    return self::config_base_uri() . $uri;
  }


  /**
   * @return bool
   */
  static public function is_public()
  {
    if ( static::$_is_public === FALSE ) return FALSE;

    return (bool)static::$_input_config;
  }

  /**
   * Includes default stylesheet and javascript
   * @return string
   */
  static public function include_default_resources()
  {
    $files = array();
    if ( ($resources = static::default_stylesheets()) ) $files = array_merge($files, $resources);
    if ( ($resources = static::default_javascripts()) ) $files = array_merge($files, $resources);

    return self::_include_resource($files);
  }


  /**
   * @param string $uri
   * @param array $input
   * @param bool $uri_has_prefix
   * @return null
   * @throws Exception
   */
  static public function create_from_uri( $uri, array $input = NULL, $uri_has_prefix = TRUE )
  {
    if ( self::$_standalone_instance )
    {
      throw new Exception('Another component is already running as the standalone instance');
    }
    // be sure uri is a string
    $uri = '' . $uri;
    if ( $uri_has_prefix )
    {
      $prefix     = self::config_base_uri();
      $prefix_len = strlen($prefix);
      if ( substr($uri, 0, $prefix_len) !== $prefix ) return NULL;
      $uri = substr($uri, $prefix_len);
    }
    $segments = explode('/', str_replace('-', '_', strtolower($uri)));
    if ( ($namespace = static::config_root_namespace()) ) array_unshift($segments, $namespace);
    $class = implode('\\', $segments);
    if ( !class_exists($class) ) return NULL;

    $data = $class::parse_standalone_input($input);
    /** @var Component $component */
    $component = new $class($data);

    return self::$_standalone_instance = $component->_setup();
  }

  /**
   * @return string|null
   */
  static public function default_layout()
  {
    return self::_default_file('layout.php', get_called_class());
  }

  /**
   * @return string|null
   */
  static public function default_template()
  {
    return self::_default_file('template.php', get_called_class());
  }

  /**
   * @param null|string $class
   * @return string[]
   */
  static public function default_stylesheets( $class = NULL )
  {
    static $cache = array();
    if ( !$class ) $class = get_called_class();
    if ( $class === __CLASS__ ) return array();
    if ( isset($cache[$class]) ) return $cache[$class];

    $res = array();
    foreach ( self::used_components($class) as $component_class )
    {
      $res = array_merge($res, self::default_stylesheets($component_class));
    }
    if ( ($file = self::_default_file('stylesheet.css', $class)) ) $res[] = $file;

    return $cache[$class] = array_keys(array_flip($res));
  }

  /**
   * @param null|string $class
   * @return string[]
   */
  static public function default_javascripts( $class = NULL )
  {
    static $cache = array();
    if ( !$class ) $class = get_called_class();
    if ( $class === __CLASS__ ) return array();
    if ( isset($cache[$class]) ) return $cache[$class];

    $res = array();
    foreach ( self::used_components($class) as $component_class )
    {
      $res = array_merge($res, self::default_javascripts($component_class));
    }
    if ( ($file = self::_default_file('javascript.js', $class)) ) $res[] = $file;

    return $cache[$class] = array_keys(array_flip($res));
  }


  /**
   * @param null|string $class
   * @return string[]
   */
  static public function used_components( $class = NULL )
  {
    static $cache = array();
    if ( !$class ) $class = get_called_class();
    if ( $class === __CLASS__ ) return array();
    if ( isset($cache[$class]) ) return $cache[$class];

    $parent = get_parent_class($class);
    $list   = self::used_components($parent);
    $prop   = self::_class($class)->getProperty('_used_components');
    if ( $prop->getDeclaringClass()->getName() === $class )
    {
      $list = array_merge($list, $class::$_used_components);
    }

    return $cache[$class] = array_keys(array_flip($list));
  }

  /**
   * @param string $class
   */
  static public function spl_autoload( $class )
  {
    if ( substr($class, 0, strlen($namespace = static::config_root_namespace()) + 1) === $namespace . '\\' )
    {
      require_once static::_get_base_path($class) . '/component.php';
    }
  }

  /**
   * @param null|string $suffix
   * @return string
   */
  static public function generate_id( $suffix = NULL )
  {
    if ( !$suffix ) $suffix = substr(md5(rand()), 0, 6);

    return static::component_name() . '.' . $suffix;
  }

  /**
   * @return mixed
   */
  static public function component_name()
  {
    static $cache = array();
    $class = get_called_class();
    if ( !isset($cache[$class]) )
    {
      $cache[$class] = str_replace(
        '\\', '.', str_replace(
          '_', '-', strtolower(substr(
            $class, strlen(static::config_root_namespace()) + 1
          ))
        )
      );
    }

    return $cache[$class];
  }


  /**
   * @param mixed $arg
   * @return Component
   * @throws Exception
   */
  static public function instance_for( $arg )
  {
    $my_class = get_called_class();
    if ( $my_class === __CLASS__ ) throw new Exception(__FUNCTION__ . ' must be called using a component class');
    $key = $my_class . ':' . static::build_instance_key($arg);
    if ( isset(self::$_instances[$key]) ) return self::$_instances[$key];

    self::$_instances[$key] = $component = new static(static::build_instance_data($arg));
    $component->_setup();

    return $component;
  }

  /**
   * @param mixed $arg
   * @return string
   * @throws Exception
   */
  static public function build_instance_key( $arg )
  {
    throw new Exception('You must define ' . __FUNCTION__ . ' method to use instance_for');
  }

  /**
   * @param mixed $arg
   * @throws Exception
   * @return array
   */
  static public function build_instance_data( $arg )
  {
    throw new Exception('You must define ' . __FUNCTION__ . ' method to use instance_for');
  }

  /**
   * @param string $file
   * @param string $kind
   * @return string
   */
  static public function get_resource_content( $file, $kind )
  {
    $content = file_get_contents($file);
    if ( $kind === 'js' )
    {
      $content = '!function(){' . $content . '}.call(this);';
    }

    return $content;
  }


  /**
   * @param array $config
   * @param bool $register_autoload
   * @return bool
   */
  static public function configure( array $config = array(), $register_autoload = TRUE )
  {
    $class = get_called_class();
    if ( !($return = static::_configure($config, $class)) && $register_autoload )
    {
      spl_autoload_register(array($class, 'spl_autoload'));
    }

    return $return;
  }


  /**
   * @param array $in
   * @param bool $i2d
   * @return array
   * @throws Exception
   */
  static private function _map_input_and_data( $in, $i2d = TRUE )
  {
    if ( !$in ) return array();
    $class = self::_class($my_class = get_called_class());
    if ( ($parent = $class->getParentClass()) && ($parent = $parent->getName()) !== __CLASS__ )
    {
      $out = $parent::{$i2d ? 'parse_standalone_input' : 'build_standalone_input'}($in);
    }
    else
    {
      $out = array();
    }
    if (
      $my_class !== __CLASS__
      && ($prop = $class->getProperty('_input_config'))
      && $prop->getDeclaringClass()->getName() === $my_class
    )
    {
      foreach ( $my_class::$_input_config as $input_name => $data_conf )
      {
        if ( is_string($data_conf) ) $data_conf = array('name' => $data_conf);
        if ( !isset($data_conf['name']) )
        {
          if ( is_int($input_name) ) throw new Exception("No name given for input to data mapper");
          $data_conf['name'] = $input_name;
        }
        else if ( is_int($input_name) )
        {
          $input_name = $data_conf['name'];
        }
        $data_name = $data_conf['name'];
        $in_key    = $i2d ? $input_name : $data_name;
        $out_key   = $i2d ? $data_name : $input_name;
        if ( array_key_exists($in_key, $in) )
        {
          $out[$out_key] = $my_class::{$i2d ? 'parse_input_value' : 'build_input_value'}(
            $data_name, $in[$in_key], $data_conf
          );
        }
      }
    }

    return $out;
  }

  /**
   * @param string|string[] $file
   * @return string
   */
  static private function _include_resource( $file )
  {
    static $included = array();

    $files = is_array($file) ? $file : array($file);
    $out   = '';
    foreach ( $files as $file )
    {
      if ( isset($included[$file]) ) continue;
      $included[$file] = TRUE;

      if ( self::$_include_resource )
      {
        $out .= call_user_func(self::$_include_resource, $file);
      }
      else
      {
        $kind = explode('.', strtolower($file));
        if ( count($kind) >= 2 )
        {
          $kind = array_pop($kind);
          if ( $kind === 'css' )
          {
            $out .= '<style type="text/css" data-for-component="' . static::component_name() . '">'
              . static::get_resource_content($file, $kind)
              . '</style>';
          }
          else if ( $kind === 'js' )
          {
            $out .= '<script type="text/javascript" data-for-component="' . static::component_name() . '">'
              . static::get_resource_content($file, $kind)
              . '</script>';
          }
        }
      }
    }

    return $out;
  }


  /**
   * @param string $name
   * @param null|string $class
   * @return null|string|string[]
   */
  static private function _default_file( $name, $class = NULL )
  {
    static $cache = array();
    if ( !$class ) $class = get_called_class();
    $cache_key = "{$class}:{$name}";
    if ( array_key_exists($cache_key, $cache) )
    {
      $file = $cache[$cache_key];
    }
    else
    {
      $file = $class::_get_base_path() . '/' . $name;
      if ( !file_exists($file) )
      {
        $file = $class === __CLASS__
          ? NULL
          : self::_default_file($name, get_parent_class($class));
      }
      $cache[$cache_key] = $file;
    }

    return $file;
  }

  /**
   * @param bool $from_toString
   * @return string
   */
  public function get_html_content( $from_toString = FALSE )
  {
    return $this->render_content();
  }


  /**
   * Called before the component will be rendered
   */
  public function before_render()
  {

  }


  /**
   * Called after the component html has been generated
   * @param string $content
   */
  public function after_render( $content )
  {
  }


  /**
   * @param bool $include_resources
   * @param bool $in_layout
   * @param bool $in_tag
   * @return string
   */
  public function render( $include_resources = TRUE, $in_layout = TRUE, $in_tag = TRUE )
  {
    $content = '';
    $this->before_render();
    if ( $in_tag ) $content .= $this->open_tag();
    // null content means lonely tag
    if ( ($body = $this->render_content()) !== NULL ) $content .= $body . ($in_tag ? $this->close_tag() : '');
    // decorate...
    if ( $in_layout && ($file = $this->layout()) )
    {
      $content = $this->_render($file, array('content' => $content));
    }
    // ...and add inline resources if any
    if ( $include_resources )
    {
      $content .= $this->include_resources();
    }

    $this->after_render($content);

    return $content;
  }

  public function __toString()
  {
    try
    {
      $content = $this->render();
    }
    catch ( \Exception $e )
    {
      if ( $this->config_debug_mode )
      {
        $content = '<h3>Error in component <var>' . static::component_name() . '</var>:</h3>'
          . '<pre>' . $e->__toString() . '</pre>';
      }
      else
      {
        $content = '<pre>error</pre>';
      }
    }

    return $content;
  }

  /**
   * @param string $file
   * @param array $extra
   * @return string
   * @throws \Exception
   */
  private function _render( $file, array $extra = NULL )
  {
    if ( $extra ) extract($extra);
    ob_start();
    try
    {
      include $file;
    }
    catch ( \Exception $err )
    {
      ob_end_clean();
      throw $err;
    }

    return ob_get_clean();
  }

  /**
   * @return $this
   * @throws Exception
   */
  private function _setup()
  {
    $this->setup();

    $this->_is_auto_id = !$this->get_attribute('id');
    if ( $this->_is_auto_id ) $this->set_id();

    $rights = $this->check_rights();
    if ( !$rights )
    {
      throw new Exception('operation not allowed');
    }

    $this->initialize();


    return $this;
  }

  /**
   * @param string $class
   * @return string
   */
  protected static function _get_base_path( $class = NULL )
  {
    if ( $class === NULL ) $class = get_called_class();

    return static::config_root_path() . '/' . strtolower(str_replace('\\', '/', substr($class, strlen(static::config_root_namespace()) + 1)));
  }

  /**
   * @param string|null $class
   * @return string[]
   */
  private static function _merged_data( $class = NULL )
  {
    static $cache = array();
    $my_class = $class ? $class : get_called_class();
    if ( isset($cache[$my_class]) ) return $cache[$my_class];
    if ( $my_class === __CLASS__ ) return $cache[$my_class] = array();

    $class        = self::_class($my_class);
    $parent_class = $class->getParentClass();
    $prop         = $class->getProperty('_merged_data_names');
    $defaults     = $class->getDefaultProperties();

    // get parent class values
    $data = self::_merged_data($parent_class->getName());

    // merge known ones
    foreach ( $data as $name => &$value )
    {
      $p = $class->getProperty($name);
      if ( $p->getDeclaringClass()->getName() === $my_class )
      {
        $val = $defaults[$name];
        if ( $val ) $value = self::_merge($name, $value, $val);
      }
    }
    unset($value);

    // add possible new ones
    if ( $prop->getDeclaringClass()->getName() === $my_class )
    {
      foreach ( $prop->getValue() as $name )
      {
        if ( isset($data[$name]) ) continue;
        if ( $class->hasProperty($name) )
        {
          $val = $defaults[$name];
          if ( !$val ) $val = array();
        }
        else
        {
          $val = array();
        }
        $data[$name] = $val;
      }
    }

    return $cache[$my_class] = $data;
  }

  /**
   * @param string $key
   * @param array $base
   * @param array $extend
   * @return array
   */
  private static function _merge( $key, $base, $extend )
  {
    if ( $key === 'attributes' )
    {
      return self::merge_attributes($base, $extend);
    }

    return array_merge($base, $extend);
  }


  /**
   * @param string $name
   * @return \ReflectionClass
   */
  static private function _class( $name )
  {
    static $cache = array();
    if ( isset($cache[$name]) ) return $cache[$name];

    return $cache[$name] = new \ReflectionClass($name);
  }


  /**
   * @param string $string
   * @return string
   */
  static private function _css_escape( $string )
  {
    return str_replace(
      array(':', '.', '[', ']', ',', '=', '\\'),
      array('\\:', '\\.', '\\[', '\\]', '\\,', '\\=', '\\\\'),
      $string
    );
  }
}
