<?php

namespace Drupal\oscarbot_api\lib\logger;

use Psr\Log\LoggerInterface as LogLoggerInterface;

/**
 * Interface para el custom logger.
 */
interface LoggerInterface extends LogLoggerInterface {

  /**
   * Status code for 'Transaction created' event.
   */
  const STATUS_CODE_TRANSACTION_STARTED = 90000;

  /**
   * Status code for 'Transaction ended' event.
   */
  const STATUS_CODE_TRANSACTION_ENDED = 90001;

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap();

  /**
   * Genera una nueva entrada en la tabla de logs del servidor.
   *
   * @param array $table_data
   *   Valores para los campos de la tabla.
   *
   * @return bool
   *   TRUE si la entrada ha sido creada.
   */
  public function createLogServerData(array $table_data) : bool;

  /**
   * Obtiene los datos de un log del servidor.
   *
   * @param int $id
   *   Identificador del Log.
   *
   * @return array
   *   Array con todos los campos de la tabla.
   */
  public function loadLogServerData(int $id);

  /**
   * Obtiene el total de entradas registradas en el log del servidor.
   *
   * @return int
   *   Total de entradas.
   */
  public function getTotalLogServerData();

  /**
   * Genera una nueva entrada en la tabla de logs del cliente.
   *
   * @param array $table_data
   *   Valores para los campos de la tabla.
   *
   * @return bool
   *   TRUE si la entrada ha sido creada.
   */
  public function createLogClientData(array $table_data) : bool;

  /**
   * Obtiene los datos de un log del cliente.
   *
   * @param int $id
   *   Identificador del Log.
   *
   * @return array
   *   Array con todos los campos de la tabla.
   */
  public function loadLogClientData(int $id);

  /**
   * Obtiene el total de entradas registradas en el log del cliente.
   *
   * @return int
   *   Total de entradas.
   */
  public function getTotalLogClientData();

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void;

  /**
   * Servicio para el log de Drupal.
   *
   * @return \Psr\Log\LoggerInterface
   *   Servicio para el log del módulo actual.
   */
  public function loggerDrupal();

  /**
   * Elimina todos los logs.
   *
   * @return array
   *   Array multidimensional que incluye:
   *   - num_events_server: número de eventos del servidor eliminadas.
   *   - num_events_client: número de logs de eventos del cliente eliminados.
   */
  public function borrarLogs();

}
