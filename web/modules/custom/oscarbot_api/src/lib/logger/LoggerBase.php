<?php

namespace Drupal\oscarbot_api\lib\logger;

use Drupal\Core\Logger\RfcLogLevel;

use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

use Drupal\oscarbot_api\lib\Traits\GlobalTrait;

/**
 * Clase base para la gestión del log custom.
 */
abstract class LoggerBase {

  use LoggerTrait;
  use GlobalTrait;

  /**
   * Nombre del módulo.
   *
   * Usado en el logger, timer, etc.
   */
  const MODULE_NAME = 'oscarbot_api';

  /**
   * Map of PSR3 log constants to RFC 5424 log constants.
   *
   * @var array
   */
  protected $levelTranslation = [
    LogLevel::EMERGENCY => RfcLogLevel::EMERGENCY,
    LogLevel::ALERT => RfcLogLevel::ALERT,
    LogLevel::CRITICAL => RfcLogLevel::CRITICAL,
    LogLevel::ERROR => RfcLogLevel::ERROR,
    LogLevel::WARNING => RfcLogLevel::WARNING,
    LogLevel::NOTICE => RfcLogLevel::NOTICE,
    LogLevel::INFO => RfcLogLevel::INFO,
    LogLevel::DEBUG => RfcLogLevel::DEBUG,
  ];

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    // This function is defined as abstract by LoggerTrait.
  }

}
