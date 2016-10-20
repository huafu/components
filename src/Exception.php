<?php
/**
 * @author Huafu Gandon <huafu.gandon@gmail.com>
 * @since 2016-10-20
 */

namespace Huafu\Components;


/**
 * Class Exception
 * @package Huafu\Components
 */
class Exception extends \Exception
{
  /** @var Component */
  public $component = NULL;


  public function __construct( $message = '', $code = 0, Exception $previous = NULL, Component $component = NULL )
  {
    $this->component = $component;
    parent::__construct($message, $code, $previous);
  }
}
