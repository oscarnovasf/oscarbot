<?php

namespace Drupal\oscarbot_api\lib\logger;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Estructura de tablas y vistas del log.
 */
class LoggerDatabaseHandler {

  use StringTranslationTrait;

  /**
   * Nombre del módulo.
   */
  const MODULE_NAME = 'oscarbot_api';

  /**
   * Array con las tablas a crear.
   */
  const TABLES = [
    'server' => 'api_calls_logs_server',
    'client' => 'api_calls_logs_client',
  ];

  /**
   * Obtiene el schema de la tabla de logs de cada operación del servidor.
   *
   * @return array
   *   Array con el schema de la tabla.
   */
  public static function getTableLogsServerSchema() : array {
    return self::getGlobalSchema(self::TABLES['server']);
  }

  /**
   * Obtiene la estructura para vistas de la tabla de logs de cada operación del servidor.
   *
   * @return array
   *   Array con la información necesaria.
   */
  public static function getTableLogsServerViewsData() : array {
    return self::getGlobalViewsData('Log del API Rest Servidor');
  }

  /**
   * Obtiene el schema de la tabla de logs de cada operación del cliente.
   *
   * @return array
   *   Array con el schema de la tabla.
   */
  public static function getTableLogsClientSchema() : array {
    return self::getGlobalSchema(self::TABLES['client']);
  }

  /**
   * Obtiene la estructura para vistas de la tabla de logs de cada operación del cliente.
   *
   * @return array
   *   Array con la información necesaria.
   */
  public static function getTableLogsClientViewsData() : array {
    return self::getGlobalViewsData('Log del API Rest Cliente');
  }

  /**
   * Obtiene el schema general de las tablas a crear.
   *
   * @param string $table_name
   *   Nombre de la tabla que se desea crear.
   *
   * @return array
   *   Array con el schema de la tabla.
   */
  private static function getGlobalSchema(string $table_name) : array {
    $schema[$table_name] = [
      'description' => 'Esta tabla contiene todas las peticiones del servidor.',
      'fields' => [
        'id' => [
          'type'        => 'serial',
          'not null'    => TRUE,
        ],
        'function_name' => [
          'type'        => 'varchar',
          'length'      => 90,
          'not null'    => TRUE,
          'default'     => '',
        ],
        'message' => [
          'type'        => 'text',
          'not null'    => TRUE,
          'size'        => 'big',
        ],
        'variables' => [
          'type'        => 'blob',
          'not null'    => TRUE,
          'size'        => 'big',
        ],
        'severity' => [
          'type'        => 'int',
          'unsigned'    => TRUE,
          'not null'    => TRUE,
          'default'     => 0,
          'size'        => 'tiny',
        ],
        'location' => [
          'type'        => 'text',
          'not null'    => TRUE,
        ],
        'referer' => [
          'type'        => 'text',
          'not null'    => FALSE,
        ],
        'hostname' => [
          'type'        => 'varchar_ascii',
          'length'      => 128,
          'not null'    => TRUE,
          'default'     => '',
        ],
        'timestamp' => [
          'type'        => 'int',
          'not null'    => TRUE,
          'default'     => 0,
        ],
        'data' => [
          'type'        => 'blob',
          'default'     => NULL,
          'size'        => 'big',
        ],
        'uid' => [
          'type'        => 'int',
          'unsigned'    => TRUE,
          'not null'    => TRUE,
          'default'     => 0,
          'description' => 'The {users}.uid of the user who triggered the event.',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'function_name' => ['function_name'],
        'severity'      => ['severity'],
        'uid'           => ['uid'],
      ],
    ];

    return $schema;
  }

  /**
   * Obtiene la estructura global para vistas de la tabla de logs.
   *
   * @param string $group_name
   *   Nombre que se le desea dar al grupo.
   *
   * @return array
   *   Array con la información necesaria.
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  private static function getGlobalViewsData(string $group_name) : array {
    $table = [];

    $table['table']['group'] = $group_name;

    $table['table']['base'] = [
      'field' => 'id',
      'title' => $group_name,
      'help'  => t('Contains a list of log entries.'),
    ];

    $table['id'] = [
      'title' => t('ID'),
      'help'  => t('Unique log event ID.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['function_name'] = [
      'title' => t('Function name'),
      'help'  => t("Function's name related to the log entry."),
      'field' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['message'] = [
      'title' => t('Message'),
      'help'  => t('The actual message of the log entry.'),
      'field' => [
        'id' => 'custom_logger_field_message',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['variables'] = [
      'title' => t('Variables'),
      'help'  => t('The variables of the log entry in a serialized format.'),
      'field' => [
        'id' => 'serialized',
        'click sortable' => FALSE,
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['severity'] = [
      'title' => t('Severity level'),
      'help'  => t('The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).'),
      'field' => [
        'id' => 'machine_name',
        'options callback' => 'Drupal\oscarbot_api\lib\logger\Logger::getLogLevelClassMap',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'Drupal\Core\Logger\RfcLogLevel::getLevels',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['location'] = [
      'title' => t('Location'),
      'help'  => t('URL of the origin of the event.'),
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['referer'] = [
      'title' => t('Referer'),
      'help'  => t('URL of the previous page.'),
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['hostname'] = [
      'title' => t('Hostname'),
      'help'  => t('Hostname of the user who triggered the event.'),
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['timestamp'] = [
      'title' => t('Timestamp'),
      'help'  => t('Date when the event occurred.'),
      'field' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date',
      ],
    ];

    $table['data'] = [
      'title' => t('Data'),
      'help'  => t('Serialized array of data such as the callback invoked when the feedback endpoint is processed.'),
      'field' => [
        'id' => 'serialized',
        'click sortable' => FALSE,
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $table['uid'] = [
      'title' => t('User'),
      'help'  => t('The user ID of the user on which the API function was processed.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'relationship' => [
        'title'      => t('User'),
        'help'       => t('The user on which the API function was processed.'),
        'base'       => 'users_field_data',
        'base field' => 'uid',
        'id' => 'standard',
      ],
    ];

    return $table;
  }

}
