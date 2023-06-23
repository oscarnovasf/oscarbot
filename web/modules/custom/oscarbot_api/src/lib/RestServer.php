<?php

namespace Drupal\oscarbot_api\lib;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use Drupal\oscarbot_api\lib\Traits\server\TestTrait;
use Drupal\oscarbot_api\lib\Traits\server\UsersTrait;

use OpenApi\Annotations as OA;

/**
 * Define los diferentes endpoints disponibles en el API.
 *
 * @OA\Info(
 *   title="Documentación del API",
 *   version="0.0.1",
 *   @OA\Contact(
 *     email="contact@example.com"
 *   ),
 *   @OA\License(
 *     name="Apache 2.0",
 *     url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *   )
 * )
 *
 * @OA\SecurityScheme(
 *   type="apiKey",
 *   name="X-Backend-Gateway-Token",
 *   in="header",
 *   securityScheme="api_key"
 * )
 *
 * @OA\SecurityScheme(
 *   type="http",
 *   securityScheme="basicAuth",
 *   scheme="basic",
 * )
 */
class RestServer extends RestServerBase {

  use StringTranslationTrait;

  /* TODO Añadir los traits propios del server */
  use TestTrait;
  use UsersTrait;

}
