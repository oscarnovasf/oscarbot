<?php

/**
 * @file
 * Provide views data for oscarbot_api.module.
 */

use Drupal\oscarbot_api\lib\logger\LoggerDatabaseHandler;

/**
 * Implements hook_views_data().
 */
function oscarbot_api_views_data() {
  /* Tabla general con las operaciones del Servidor */
  $data[LoggerDatabaseHandler::TABLES['server']] = LoggerDatabaseHandler::getTableLogsServerViewsData();

  /* Tabla general con las operaciones del Cliente */
  $data[LoggerDatabaseHandler::TABLES['client']] = LoggerDatabaseHandler::getTableLogsClientSchema();

  return $data;
}
