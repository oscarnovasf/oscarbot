<?php

namespace Drupal\oscarbot_api\Controller;

use OpenApi\Generator;
use Drupal\user\UserDataInterface;
use Drupal\Core\Access\AccessResult;

use Drupal\oscarbot_api\lib\RestServer;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\oscarbot_api\lib\swagger\SwaggerUiLibraryDiscovery;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Gestiona el API Server.
 */
class RestServerController extends ControllerBase implements ContainerInjectionInterface, AccessInterface {

  /**
   * Servicio de servidor REST.
   *
   * @var \Drupal\oscarbot_api\lib\RestServer
   */
  protected $restServer;

  /**
   * Servicio de datos de usuario.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Configuración del módulo.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moduleSettings;

  /**
   * Interfaz para el idioma.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Servicio para obtener path del módulo.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Servicio para encontrar librerías (Drupal).
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libaryDiscoveryDrupal;

  /**
   * Servicio para encontrar librerías (Custom).
   *
   * @var \Drupal\oscarbot_api\lib\swagger\SwaggerUiLibraryDiscovery
   */
  protected $libaryDiscovery;

  /**
   * Constructor de la clase.
   *
   * @param \Drupal\oscarbot_api\lib\RestServer $rest_server
   *   Servicio de servidor rest.
   * @param \Drupal\user\UserDataInterface $user_data
   *   Servicio de datos de usuario.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Servicio de datos de configuración.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $path_resolver
   *   Servicio para obtener path del módulo.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libary_discovery_drupal
   *   Servicio para encontrar librerías (Drupal).
   * @param \Drupal\oscarbot_api\lib\swagger\SwaggerUiLibraryDiscovery $libary_discovery
   *   Servicio para encontrar librerías (Custom).
   */
  public function __construct(
    RestServer $rest_server,
    UserDataInterface $user_data,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    ExtensionPathResolver $path_resolver,
    LibraryDiscoveryInterface $libary_discovery_drupal,
    SwaggerUiLibraryDiscovery $libary_discovery
  ) {
    $this->restServer = $rest_server;
    $this->userData = $user_data;
    $this->moduleSettings = $config_factory->get('oscarbot_api.settings');
    $this->languageManager = $language_manager;
    $this->pathResolver = $path_resolver;
    $this->libaryDiscoveryDrupal = $libary_discovery_drupal;
    $this->libaryDiscovery = $libary_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oscarbot_api.rest_server'),
      $container->get('user.data'),
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('extension.path.resolver'),
      $container->get('library.discovery'),
      $container->get('oscarbot_api.swagger.ui_library_discovery')
    );
  }

  /**
   * Check access to the backend rest service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $group
   *   Grupo de servicios.
   * @param string $service_name
   *   The name of the backend service.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Exception generated when access is not allowed.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception generated if the service is unknown.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Exception generated if the request is malformed.
   * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
   *   Exception generated if the method is not allowed.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function backendAccess(Request $request,
                                string $group = NULL,
                                string $service_name = NULL) {
    $service_route = $group . '/' . $service_name;

    /* Compruebo que el token esté definido en el módulo */
    if (!($backend_gateway_token = $this->moduleSettings->get('backend_gateway_token'))) {
      $this->generateErrorLog($request, 'No token defined', $service_route);
      throw new BadRequestHttpException($this->t('Token not defined.'));
    }

    /* Compruebo que se envíe el token en el header de la petición */
    if (!($request_token = $request->headers->get('X-Backend-Gateway-Token'))) {
      $this->generateErrorLog($request, 'No send token', $service_route);
      throw new AccessDeniedHttpException($this->t('Access denied.'));
    }

    /* Compruebo que los tokens coinciden */
    if ($request_token !== $backend_gateway_token) {
      $this->generateErrorLog($request, 'Bad token: ' . $request_token, $service_route);
      throw new AccessDeniedHttpException($this->t('Access denied.'));
    }

    /* Compruebo que el servicio solicitado exista, que sea accesible para el
     * usuario y que el método usado sea el esperado. */
    $services = $this->gatewayServicesInfo();
    $account = $this->currentUser();
    $method = $request->getMethod();
    if (empty($service_name) || !isset($services[$group][$service_name])) {
      $this->generateErrorLog($request, 'Unknown service', $service_route);
      throw new NotFoundHttpException($this->t('Not found (unknown service).'));
    }
    elseif ($account->isAnonymous() && $services[$group][$service_name]['private']) {
      $this->generateErrorLog($request, 'Access denied', $service_route);
      throw new AccessDeniedHttpException($this->t('Access denied (only register users).'));
    }
    elseif ($method != $services[$group][$service_name]['method']) {
      $this->generateErrorLog($request, 'Method not allowed: ' . $method, $service_route);
      throw new MethodNotAllowedHttpException([$services[$group][$service_name]['method']],
                                              $this->t('Method not allowed.'));
    }

    /* Verifico que el contenido sea válido */
    $content = $request->getContent();
    if (!empty($content)) {
      $content = json_decode($content, TRUE);
    }
    if (empty($content) && $services[$group][$service_name]['has_params'] == TRUE) {
      $this->generateErrorLog($request, 'Malformed request (missing data)', $service_route);
      throw new BadRequestHttpException($this->t('Malformed request (missing data).'));
    }

    /* Si el método es público entonces permito el acceso */
    if (FALSE == $services[$group][$service_name]['private']) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /* Si el método es privado compruebo que venga con el nombre de usuario */
    if ($services[$group][$service_name]['private']) {
      if (empty($content['username'])) {
        $this->generateErrorLog($request, 'Access denied (missing credentials)', $service_route);
        throw new AccessDeniedHttpException($this->t('Access denied (missing credentials).'));
      }
      if ($content['username'] != $account->getAccountName()) {
        $this->generateErrorLog($request, 'Access denied (invalid credentials)', $service_route);
        throw new AccessDeniedHttpException($this->t('Access denied (invalid credentials).'));
      }
    }

    return AccessResult::allowed()->cachePerPermissions();
  }

  /**
   * Comprueba si se tienen permisos suficientes para ver documentación del API.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Usuario logueado.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessApiDoc(AccountInterface $account) {
    $active_modules = $this->moduleSettings->get('active_modules');
    if (in_array('server', $active_modules) && $account->hasPermission('access oscarbot_api api doc')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden($this->t('You must enabled server module')->__toString());
  }

  /**
   * Genera un archivo json con las especificaciones para Swagger UI.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Json.
   */
  public function generateSwagger() {
    $module_path = $this->pathResolver->getPath('module', "oscarbot_api");

    $openapi = Generator::scan([$module_path . '/src/lib']);
    $content = Json::decode($openapi->toJson());

    return new JsonResponse($content);
  }

  /**
   * Genera la documentación vía Swagger UI.
   */
  public function getSwaggerDocumentation() {
    $resultado = [];
    $library_name = 'oscarbot_api.swagger_ui_integration';

    $library_discovery = $this->libaryDiscoveryDrupal;
    $swagger_ui_library_discovery = $this->libaryDiscovery;

    // The Swagger UI library integration is only registered if the Swagger UI
    // library directory and version is correct.
    if ($library_discovery->getLibraryByName('oscarbot_api', $library_name) === FALSE) {
      $resultado = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [$this->t('The Swagger UI library is missing, incorrectly defined or not supported.')],
        ],
      ];
    }
    else {
      $library_dir = $swagger_ui_library_discovery->libraryDirectory();
      // Set the oauth2-redirect.html file path for OAuth2 authentication.
      $oauth2_redirect_url = \Drupal::request()->getSchemeAndHttpHost() . '/' . $library_dir . '/dist/oauth2-redirect.html';
      $swagger_file_url = \Drupal::request()->getSchemeAndHttpHost() . '/api/info.json';

      $swagger_file_content = $this->generateSwagger();
      if ($swagger_file_content === NULL) {
        $resultado = [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [$this->t('Could not create URL to file.')],
          ],
        ];
      }
      else {
        $resultado = [
          '#theme' => 'swagger_ui',
          '#attached' => [
            'library' => [
              'oscarbot_api/' . $library_name,
            ],
            'drupalSettings' => [
              'swaggerUIFormatter' => [
                'oauth2RedirectUrl' => $oauth2_redirect_url,
                'swaggerFile' => $swagger_file_url,
                'validator' => 'none',
                'validatorUrl' => '',
                'docExpansion' => 'list',
                'showTopBar' => FALSE,
                'sortTagsByName' => TRUE,
                'supportedSubmitMethods' => ['get', 'post'],
              ],
            ],
          ],
        ];
      }
    }

    if ($swagger_ui_library_discovery instanceof CacheableDependencyInterface) {
      $cacheable_metadata = CacheableMetadata::createFromRenderArray($resultado)
        ->merge(CacheableMetadata::createFromObject($swagger_ui_library_discovery));
      $cacheable_metadata->applyTo($resultado);
    }

    return $resultado;

  }

  /**
   * Llama al servicio solicitado.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Datos de la petición.
   * @param string $group
   *   Grupo de servicios.
   * @param string $service_name
   *   Nombre del servicio solicitado.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   An array in JSON format, with the response returned by the service.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Excepción generada si falla el servicio.
   */
  public function backendGateway(Request $request,
                                 string $group = NULL,
                                 string $service_name = NULL) {
    $service_route = $group . '/' . $service_name;

    // Decode request data; note validation is done in access callback.
    $data = json_decode($request->getContent(), TRUE);

    /* Si no existen datos, los pongo como array vacío */
    $data = $data ?? NULL;

    /* Realizo la petición */
    $result = $this->restServer->{$service_name}($data);
    if ($result && $this->restServer->performaceTracing()) {
      $result_json = Json::decode($result->getContent());

      if ($result_json['status']) {
        $type = 'notice';
        $status = 'sucessfully';
      }
      else {
        $type = 'error';
        $status = $result_json['error']['message'] ?? 'with errors';
      }

      $log_data = [
        'in' => $data,
        'out' => $result_json,
      ];
      $this->restServer->logger()->{$type}('@name service: @status.', [
        'function_name' => $service_route,
        'data'          => serialize($this->restServer->var2str($log_data)),
        'rest_type'     => 'server',

        '@name'   => $service_route,
        '@status' => $status,
      ]);
    }
    elseif (!$result) {
      // If the service failed, return a Bad Request error with the message.
      $message = $this->restServer->getLastErrorMessage();
      throw new BadRequestHttpException(!empty($message) ? $message : $this->t('Error processing service @name.', [
        '@name' => $service_route,
      ]));
    }
    return $result;
  }

  /* ***************************************************************************
   * MÉTODOS PROTECTED.
   * **************************************************************************/

  /**
   * Genera un log de error.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $message
   *   Message text.
   * @param string $service_name
   *   The name of the backend service.
   */
  protected function generateErrorLog(Request $request,
                                      string $message,
                                      string $service_name = NULL) {
    $data = json_decode($request->getContent(), TRUE);

    $response = [
      'ip' => $request->getClientIp(),
      'service_name' => $service_name,
      'message' => $message,
      'request' => $data,
    ];
    $this->restServer->logger()->error($message, [
      'function_name' => $service_name,
      'data'          => serialize($this->restServer->var2str($response)),
      'rest_type'     => 'server',
    ]);
  }

  /**
   * Retorna información sobre los servicios expuestos.
   *
   * El primer nivel de índices coincide con el nombre de la función que el
   * usuario desea llamar.
   *
   * Atributos:
   * - private:       Si se establece en TRUE se deberá enviar el username.
   * - has_params:    Indica si el servicio requiere que se le pasen parámetros.
   * - method:        Define el método admitido para el endpoint.
   *
   * @return array
   *   Array con todos los servicios expuestos y sus atributos.
   */
  protected function gatewayServicesInfo() {
    /* TODO: Añadir todos los métodos disponibles */
    $apis_disponibles = [
      'tests' => [
        'isAlive' => [
          'private'    => FALSE,
          'has_params' => FALSE,
          'method'     => 'GET',
        ],
      ],

      'user' => [
        'login' => [
          'private'    => FALSE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
        'loginStatus' => [
          'private'    => FALSE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
        'logout' => [
          'private'    => TRUE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
        'resetPass' => [
          'private'    => FALSE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
        'cancelAccount' => [
          'private'    => TRUE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
        'register' => [
          'private'    => FALSE,
          'has_params' => TRUE,
          'method'     => 'POST',
        ],
      ],
    ];

    return $apis_disponibles;
  }

}
