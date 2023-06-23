<?php

namespace Drupal\oscarbot_api\lib;

/**
 * Define las llamadas a los diferentes servicios del API.
 *
 * Los servicios están agrupados en traits.
 * Para ejecutar una llamada a un servicio haremos:
 *
 * $rest_client = \Drupal::service('oscarbot_api.rest_client');
 * $result = $rest_client->anyRestService($data);
 *
 * Sustituiremos anyRestService por el nombre de la función que queremos
 * llamar en cada momento.
 */
class RestClient extends RestClientBase {

  /* TODO Añadir los traits propios del cliente */

}
