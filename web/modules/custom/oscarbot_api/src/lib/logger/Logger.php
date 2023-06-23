<?php

namespace Drupal\oscarbot_api\lib\logger;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Almacena eventos en una tabla custom.
 */
class Logger extends LoggerBase implements LoggerInterface {

  use RfcLoggerTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerDrupal;

  /**
   * Constructor para inyectar dependencias.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The Drupal logger factory.
   */
  public function __construct(
    Connection $connection,
    LogMessageParserInterface $parser,
    TimeInterface $time,
    RequestStack $request_stack,
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->connection = $connection;
    $this->parser = $parser;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->configFactory = $config_factory;
    $this->loggerDrupal = $logger_factory->get(self::MODULE_NAME);
    $this->config = $this->configFactory->get('oscarbot_api.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getLogLevelClassMap() {
    return [
      RfcLogLevel::DEBUG => 'custom-log-debug',
      RfcLogLevel::INFO => 'custom-log-info',
      RfcLogLevel::NOTICE => 'custom-log-notice',
      RfcLogLevel::WARNING => 'custom-log-warning',
      RfcLogLevel::ERROR => 'custom-log-error',
      RfcLogLevel::CRITICAL => 'custom-log-critical',
      RfcLogLevel::ALERT => 'custom-log-alert',
      RfcLogLevel::EMERGENCY => 'custom-log-emergency',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createLogServerData(array $table_data) : bool {
    $result = (bool) $this->connection
      ->insert(LoggerDatabaseHandler::TABLES['server'])
      ->fields($table_data)
      ->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadLogServerData(int $id) {
    $fields = $this->connection
      ->select(LoggerDatabaseHandler::TABLES['server'], 'sl')
      ->fields('sl')
      ->condition('id', $id, '=')
      ->execute()
      ->fetchAssoc();
    if (!empty($fields) && !empty($fields['data'])) {
      $fields['data'] = unserialize($fields['data']);
    }
    if (!empty($fields) && !empty($fields['variables'])) {
      $fields['variables'] = unserialize($fields['variables']);
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalLogServerData() {
    $number_of_rows = $this->connection
      ->select(LoggerDatabaseHandler::TABLES['server'], 'sl')
      ->countQuery()
      ->execute()
      ->fetchField();

    return $number_of_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function createLogClientData(array $table_data) : bool {
    $result = (bool) $this->connection
      ->insert(LoggerDatabaseHandler::TABLES['client'])
      ->fields($table_data)
      ->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadLogClientData(int $id) {
    $fields = $this->connection
      ->select(LoggerDatabaseHandler::TABLES['client'], 'cl')
      ->fields('sl')
      ->condition('id', $id, '=')
      ->execute()
      ->fetchAssoc();
    if (!empty($fields) && !empty($fields['data'])) {
      $fields['data'] = unserialize($fields['data']);
    }
    if (!empty($fields) && !empty($fields['variables'])) {
      $fields['variables'] = unserialize($fields['variables']);
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalLogClientData() {
    $number_of_rows = $this->connection
      ->select(LoggerDatabaseHandler::TABLES['client'], 'cl')
      ->countQuery()
      ->execute()
      ->fetchField();

    return $number_of_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    // Remove backtrace and exception since they may contain an unserializable
    // variable.
    unset($context['backtrace'], $context['exception']);

    // Merge in defaults.
    $context += [
      'function_name' => '',
      'rest_type' => '',
      'request_uri' => '',
      'referer' => '',
      'ip' => '',
      'uid' => $this->currentUser->id(),
      'timestamp' => $this->time->getRequestTime(),
    ];

    // Some context values are only available when in a request context.
    if ($this->requestStack && $request = $this->requestStack->getCurrentRequest()) {
      $context['request_uri'] = $request->getUri();
      $context['referer'] = $request->headers->get('Referer', '');
      $context['ip'] = $request->getClientIP();
    }

    // Convert to integer equivalent for consistency with RFC 5424.
    if (is_string($level) && isset($this->levelTranslation[$level])) {
      $level = $this->levelTranslation[$level];
    }

    // Convert PSR3-style messages to \Drupal\Component\Render\FormattableMarkup
    // style, so they can be translated too in runtime.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    $data = '';
    if (!empty($context['data'])) {
      $data = serialize($context['data']);
    }

    /* Genero el log en su lugar correcto */
    $table_data = [
      'function_name' => $context['function_name'],
      'message'       => $message,
      'variables'     => serialize($message_placeholders),
      'severity'      => $level,
      'location'      => $context['request_uri'],
      'referer'       => $context['referer'],
      'hostname'      => mb_substr($context['ip'], 0, 128),
      'timestamp'     => $context['timestamp'],
      'data'          => $data,
      'uid'           => $context['uid'],
    ];
    if ($context['rest_type'] == 'server') {
      $this->createLogServerData($table_data);
    }
    elseif ($context['rest_type'] == 'client') {
      /* TODO Implementar esta funcionalidad copiando la anterior */
      //  $this->createClientData($table_data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loggerDrupal() {
    return $this->getLogger(self::MODULE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function borrarLogs() {
    /* Elimino logs servidor */
    $num_events_server = (int) $this->connection
      ->delete(LoggerDatabaseHandler::TABLES['server'])
      ->execute();
    $this->connection
      ->truncate(LoggerDatabaseHandler::TABLES['server'])
      ->execute();

    /* Elimino logs cliente */
    $num_events_client = (int) $this->connection
      ->delete(LoggerDatabaseHandler::TABLES['client'])
      ->execute();
    $this->connection
      ->truncate(LoggerDatabaseHandler::TABLES['client'])
      ->execute();

    return [
      'num_events_server' => $num_events_server,
      'num_events_client' => $num_events_client,
    ];
  }

  /**
   * Realiza una limpieza del log si se cumplen los requisitos.
   *
   * Los requisitos se establecen en la configuración del módulo.
   * Esta función está pensada para ser ejecutada en el cron del módulo.
   */
  public function limpiarLog() : void {
    $request_time = $this->time->getRequestTime();
    $log_max_time = $this->config->get('log_max_time');
    $log_max_rows = $this->config->get('log_max_rows');

    $total_num_operations_delete = 0;
    $total_num_operations_log_delete = 0;

    /* TODO: Obtengo las posibles operaciones a eliminar */

    /* TODO: Elimino todos los log de dichas operaciones */

    /* TODO: Elimino las operaciones */

    /* Genero log informativo en Drupal */
    if ($total_num_operations_delete > 0 || $total_num_operations_log_delete > 0) {
      $mensaje = $this->t('@total_operations operations and @total_log log records have been deleted', [
        '@total_operations' => $total_num_operations_delete,
        '@total_log' => $total_num_operations_log_delete,
      ]);

      $this->loggerDrupal->notice($mensaje->render());
    }
  }

}
