<?php

namespace Drupal\oscarbot_api\lib;

use Drupal\oscarbot_api\lib\Traits\GlobalTrait;
use Drupal\oscarbot_api\lib\logger\LoggerInterface;

/**
 * Métodos comunes a los servicios Rest Cliente y Servidor.
 */
abstract class RestBase {

  use GlobalTrait;

  /**
   * Nombre del módulo.
   *
   * Usado en el logger, timer, etc.
   */
  const MODULE_NAME = 'oscarbot_api';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Objeto con la configuración del módulo.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Almacena el último mensaje de error del API.
   *
   * @var string
   */
  protected $lastErrorMessage = '';

  /**
   * Almacena el valor de la configuración para realizar log de las peticiones.
   *
   * @var bool
   */
  protected $performaceTracing;

  /**
   * Almacena el valor de la configuración para realizar log de las peticiones.
   *
   * @var bool
   */
  protected $trackErrorResult;

  /**
   * Logger custom.
   *
   * @var \Drupal\custom_api_rest\lib\logger\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor de la clase.
   *
   * @param \Drupal\custom_api_rest\lib\logger\LoggerInterface $logger
   *   Custom logger.
   */
  public function __construct(
    LoggerInterface $logger
  ) {
    $this->logger = $logger;
  }

  /**
   * Obtiene todas las configuraciones del módulo.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Objeto con la configuración del módulo.
   */
  public function getSettings() {
    return $this->config->get();
  }

  /**
   * Obtiene el valor de una configuración específica.
   *
   * @param string $setting_key
   *   Nombre del parámetro de la configuración a obtener.
   *
   * @return mixed
   *   El valor de la configuración solicitada. El tipo de variable dependerá
   *   del valor almacenado en la misma.
   */
  public function getSetting(string $setting_key) {
    return $this->config->get($setting_key);
  }

  /**
   * Obtiene el último mensaje de error.
   *
   * @return string
   *   Último mensaje almacenado.
   */
  public function getLastErrorMessage() {
    return $this->lastErrorMessage;
  }

  /**
   * Establece el último mensaje de error del API.
   *
   * @param string $error_message
   *   Mensaje de error a almacenar.
   *
   * @return string
   *   El mismo mensaje establecido como parámetro.
   */
  protected function setLastErrorMessage($error_message) {
    $this->lastErrorMessage = $error_message;
    return $this->lastErrorMessage;
  }

  /**
   * Limpia el último mensaje de error del API.
   */
  protected function clearLastErrorMessage() {
    $this->lastErrorMessage = '';
  }

  /**
   * Obtiene el valor de la configuración para el registro de las peticiones.
   *
   * Este valor indica si se desea generar un log en Drupal de todas las
   * peticiones que se realizan al API.
   *
   * @return bool
   *   Valor establecido en la configuración para el parámetro
   *   performance_tracing.
   */
  public function performaceTracing() {
    if (!isset($this->performaceTracing)) {
      $this->performaceTracing = (boolean) $this->getSetting('performance_tracing');
    }
    return $this->performaceTracing;
  }

  /**
   * Obtiene el valor de la configuración para el registro de las peticiones.
   *
   * Este valor indica si se desea generar un log en Drupal de todas las
   * peticiones que devuelven un error que se realizan al API.
   *
   * @return bool
   *   Valor establecido en la configuración para el parámetro
   *   track_error_result.
   */
  public function trackErrorResult() {
    if (!isset($this->trackErrorResult)) {
      $this->trackErrorResult = (boolean) $this->getSetting('track_error_result');
    }
    return $this->trackErrorResult;
  }

  /**
   * Servicio para el log Custom.
   *
   * @return \Psr\Log\LoggerInterface
   *   Servicio para el log del módulo actual.
   */
  public function logger() {
    return $this->logger;
  }

}
