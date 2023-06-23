<?php

namespace Drupal\oscarbot_api\lib\Traits\server;

use OpenApi\Annotations as OA;

use Drupal\oscarbot_api\lib\general\ResponseHandler;

/**
 * Contiene pruebas de conexión del servidor.
 */
trait TestTrait {

  /**
   * Devuelve el estado del API.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el estado del API.
   *
   * @OA\Get(
   *   path="/api/gateway/tests/isAlive",
   *   tags={"Tests"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           description="Devolución isAlive",
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *               @OA\Property(
   *                 property="message", type="string",
   *                 description="Mensaje informativo"
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function isAlive() : object {
    $response = new ResponseHandler();

    $state = \Drupal::state()->get('system.maintenance_mode');
    if ($state) {
      $response->setErrorCode(100);
      $response->setErrorMessage('Maintenance mode on');
    }
    else {
      $datos_respuesta = [
        'message' => 'Server is OK',
      ];
      $response->setStatus(TRUE);
      $response->setResponse($datos_respuesta);
    }

    return $response->getJsonNoCacheable();
  }

}
