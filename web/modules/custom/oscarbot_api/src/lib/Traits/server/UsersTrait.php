<?php

namespace Drupal\oscarbot_api\lib\Traits\server;

use Drupal\user\Entity\User;
use Drupal\Core\Language\LanguageInterface;

use Drupal\custom_api_rest\lib\general\ResponseHandler;
use Drupal\custom_api_rest\lib\general\ValidateFunctions;

use OpenApi\Annotations as OA;

/**
 * Gestión integral de usuarios.
 */
trait UsersTrait {

  /**
   * Realiza el login del usuario en Drupal.
   *
   * @param array $data
   *   Array con los datos de login.
   *   Estructura:
   *   - username: Nombre del usuario.
   *   - password: Contraseña del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con la información del usuario.
   *
   * @OA\Post(
   *   path="/api/gateway/user/login",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         required={"username", "password"},
   *
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         ),
   *         @OA\Property(
   *           property="password", type="string",
   *           description="Password"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           description="Datos de la sesión y del usuario.",
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="session_name", type="string",
   *               description="Nombre de la sesión"
   *             ),
   *             @OA\Property(
   *               property="session_id", type="string",
   *               description="Identificador de la sesión"
   *             ),
   *             @OA\Property(
   *               property="csrf_token", type="string",
   *               description="Token que será usado en todas las llamadas al API que necesiten estar logueado"
   *             ),
   *             @OA\Property(
   *               property="current_user",
   *               description="Datos del usuario que se ha logueado",
   *               @OA\Property(
   *                 property="name", type="string",
   *                 description="Nombre de usuario"
   *               ),
   *               @OA\Property(
   *                 property="uid", type="integer",
   *                 description="Identificador único del usuario"
   *               ),
   *               @OA\Property(
   *                 property="roles", type="array",
   *                 description="Array con todos los roles del usuario",
   *                 @OA\Items()
   *               )
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function login(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    $pass_check = FALSE;
    $name = $data['username'] ?? NULL;
    $pass = $data['password'] ?? NULL;

    if ($this->currentUser->isAnonymous() && !is_null($name) && !is_null($pass)) {
      $account = user_load_by_name(trim($name));
      if ($account) {
        $pass_check = $this->password->check(trim($pass), $account->getPassword());

        if ($pass_check) {
          $this->session->migrate();
          $this->session->set('uid', $account->id());

          $this->moduleHandler->invokeAll('user_login', [$account]);
          user_login_finalize($account);

          $datos_respuesta = [
            'session_name' => $this->sessionManager->getName(),
            'session_id'   => $this->sessionManager->getId(),
            'csrf_token'   => $this->csrfToken->get(),
            'current_user' => [
              'name'  => $account->getAccountName(),
              'uid'   => $account->id(),
              'roles' => $account->getRoles(),
            ],
          ];

          $response->setStatus(TRUE);
          $response->setResponse($datos_respuesta);
        }
        else {
          $response->setErrorCode(-10);
          $response->setErrorMessage($this->t('Wrong username and/or password.'));
        }
      }
      else {
        $response->setErrorCode(-20);
        $response->setErrorMessage($this->t('Wrong username and/or password.'));
      }
    }
    else {
      $datos_respuesta = [
        'session_name' => $this->sessionManager->getName(),
        'session_id'   => $this->sessionManager->getId(),
        'csrf_token'   => $this->csrfToken->get(),
        'current_user' => [
          'name'  => $this->currentUser->getAccountName(),
          'uid'   => $this->currentUser->id(),
          'roles' => $this->currentUser->getRoles(),
        ],
      ];

      $response->setStatus(TRUE);
      $response->setResponse($datos_respuesta);
    }

    return $response->getJsonNoCacheable();
  }

  /**
   * Realiza el logout del usuario en Drupal.
   *
   * @param array $data
   *   Array con los datos de logout.
   *   Estructura:
   *   - username: Nombre del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el estado del API.
   *
   * @OA\Post(
   *   path="/api/gateway/user/logout",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\Parameter(
   *     name="X-CSRF-Token",
   *     in="header",
   *     description="CSRF-Token obtenido tras el login",
   *     required=true,
   *     @OA\Schema(type="string")
   *   ),
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="message", type="string",
   *               description="Mensaje informativo."
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function logout(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    if ($this->currentUser->getAccountName() == $data['username']) {
      $this->sessionManager->delete(\Drupal::currentUser()->id());

      $datos_respuesta = [
        'message' => 'Logout',
      ];
      $response->setResponse($datos_respuesta);
      $response->setStatus(TRUE);
    }
    else {
      $response->setErrorCode(-10);
      $response->setErrorMessage('Not found');
    }

    return $response->getJsonNoCacheable();
  }

  /**
   * Comprueba si el usuario está logueado.
   *
   * @param array $data
   *   Array con los datos de login.
   *   Estructura:
   *   - username: Nombre del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el estado del API.
   *
   * @OA\Post(
   *   path="/api/gateway/user/loginStatus",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="message", type="string",
   *               description="Mensaje informativo."
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function loginStatus(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    if ($this->currentUser->getAccountName() == $data['username']) {
      $datos_respuesta = [
        'message' => 'Logged',
      ];
      $response->setStatus(TRUE);
      $response->setResponse($datos_respuesta);
    }
    else {
      $response->setErrorCode(-10);
      $response->setErrorMessage('Not logged');
    }

    return $response->getJsonNoCacheable();
  }

  /**
   * Envía correo de recuperación de contraseña al usuario.
   *
   * @param array $data
   *   Array con los datos de recuperación.
   *   Estructura:
   *   - username: Nombre del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el resultado de la operación.
   *
   * @OA\Post(
   *   path="/api/gateway/user/resetPass",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="message", type="string",
   *               description="Mensaje informativo."
   *             ),
   *             @OA\Property(
   *               property="mail_response", type="string",
   *               description="Resultado del envío del mail."
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function resetPass(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    $name = $data['username'] ?? NULL;

    if (!is_null($name)) {
      $account = user_load_by_name(trim($name));
      if ($account) {
        $langcode = strtoupper($this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId());
        $mail = _user_mail_notify('password_reset', $account, $langcode);

        $datos_respuesta = [
          'message' => 'Sent reset password email',
          'mail_response' => $this->var2str($mail),
        ];
        $response->setStatus(TRUE);
        $response->setResponse($datos_respuesta);
      }
      else {
        $response->setErrorCode(-20);
        $response->setErrorMessage($this->t('Wrong username.'));
      }
    }
    else {
      $response->setErrorCode(-10);
      $response->setErrorMessage($this->t('Wrong username.'));
    }

    return $response->getJsonNoCacheable();
  }

  /**
   * Bloquea una cuenta de usuario.
   *
   * @param array $data
   *   Array con los datos necesarios.
   *   Estructura:
   *   - username: Nombre del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el resultado de la operación.
   *
   * @OA\Post(
   *   path="/api/gateway/user/cancelAccount",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\Parameter(
   *     name="X-CSRF-Token",
   *     in="header",
   *     description="CSRF-Token obtenido tras el login",
   *     required=true,
   *     @OA\Schema(type="string")
   *   ),
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="message", type="string",
   *               description="Mensaje informativo."
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function cancelAccount(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    $name = $data['username'] ?? NULL;

    if (!is_null($name)) {
      $account = user_load_by_name(trim($name));
      if ($account) {
        $account->block();
        $account->save();

        $datos_respuesta = [
          'message' => 'User account has been blocked.',
        ];
        $response->setStatus(TRUE);
        $response->setResponse($datos_respuesta);
      }
      else {
        $response->setErrorCode(-20);
        $response->setErrorMessage($this->t('Wrong username.'));
      }
    }
    else {
      $response->setErrorCode(-10);
      $response->setErrorMessage($this->t('Wrong username.'));
    }

    return $response->getJsonNoCacheable();
  }

  /**
   * Crea una nueva cuenta de usuario.
   *
   * Además se envía un mail al usuario creado para que pueda loguearse.
   * Se usa la plantilla del sistema "status_activated".
   *
   * @param array $data
   *   Array con los datos de la nueva cuenta.
   *   Estructura:
   *   - username: Nombre del usuario.
   *   - email: Correo electrónico del usuario.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json con el resultado de la operación.
   *
   * @OA\Post(
   *   path="/api/gateway/user/register",
   *   tags={"User"},
   *
   *   security={
   *     {"api_key": {}, "basicAuth": {}}
   *   },
   *
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         @OA\Property(
   *           property="username", type="string",
   *           description="Username"
   *         ),
   *         @OA\Property(
   *           property="email", type="string",
   *           description="e-mail"
   *         ),
   *         @OA\Property(
   *           property="role", type="string",
   *           description="User role"
   *         )
   *       )
   *     )
   *   ),
   *
   *   @OA\Response(
   *     response="200",
   *     description="json",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#components/schemas/base_response"),
   *         @OA\Schema(
   *           @OA\Property(
   *             property="response",
   *             description="Contenido de la respuesta",
   *             @OA\Property(
   *               property="id", type="number",
   *               description="Id del usuario creado."
   *             )
   *           )
   *         )
   *       }
   *     )
   *   )
   * )
   */
  public function register(array $data) : object {
    $response = new ResponseHandler();
    $datos_respuesta = [];

    /* Parámetros obligatorios */
    $username = $data['username'] ?? NULL;
    $email = $data['email'] ?? NULL;
    $role = $data['role'] ?? NULL;

    if (!is_null($username) && !is_null($email) && !is_null($role)) {
      if (ValidateFunctions::isValidEmail($email)) {
        $user = User::create();

        $user->setPassword("Abc123;");
        $user->enforceIsNew();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->set('status', TRUE);

        $user->addRole($role);

        $user->save();

        $datos_respuesta = [
          'id' => $user->id(),
        ];

        $op = 'status_activated';
        _user_mail_notify($op, $user);

        $response->setStatus(TRUE);
        $response->setResponse($datos_respuesta);
      }
      else {
        $response->setErrorCode(-20);
        $response->setErrorMessage($this->t('Invalid email format.'));
      }
    }
    else {
      $response->setErrorCode(-10);
      $response->setErrorMessage($this->t('Wrong data.'));
    }

    return $response->getJsonNoCacheable();
  }

}
