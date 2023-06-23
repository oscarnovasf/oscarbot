<?php

namespace Drupal\oscarbot_api\lib;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

use Drupal\oscarbot_api\lib\logger\LoggerInterface;

/**
 * Defines la base para las peticiones Rest al API.
 */
abstract class RestServerBase extends RestBase {

  /**
   * Servicio de cliente REST.
   *
   * @var \Drupal\oscarbot_api\lib\ResClient
   */
  protected $restClient;

  /**
   * Almacena el valor de la configuración para activar la caché de resultados.
   *
   * @var bool
   */
  protected $cacheResults;

  /**
   * Parámetros request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Sesión del usuario.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Sesión del usuario.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Gestión de contraseñas.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Gestión de módulos.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Token CSRF.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Interfaz para el idioma.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construye una nueva instancia del servicio.
   *
   * @param \Drupal\oscarbot_api\lib\RestClient $rest_client
   *   Servicio de servidor rest.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Parámetros request.
   * @param \Drupal\oscarbot_api\lib\logger\LoggerInterface $logger
   *   Custom logger.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Servicio de gestión de sesiones.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Sesión.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   Servicio de gestión de contraseñas.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Gestor de módulos.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   Gestión de tokens de acceso.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Usuario actual.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(
    RestClient $rest_client,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    LoggerInterface $logger,
    SessionManagerInterface $session_manager,
    Session $session,
    PasswordInterface $password,
    ModuleHandlerInterface $module_handler,
    CsrfTokenGenerator $csrf_token,
    AccountInterface $current_user,
    LanguageManagerInterface $language_manager
  ) {
    $this->restClient = $rest_client;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('oscarbot_api.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->sessionManager = $session_manager;
    $this->session = $session;
    $this->password = $password;
    $this->moduleHandler = $module_handler;
    $this->csrfToken = $csrf_token;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;

    parent::__construct($logger);
  }

  /**
   * Obtiene el valor de la configuración para la caché de resultados.
   *
   * Este valor indica si los resultados del API deben ser añadidos a la caché
   * de Drupal o no.
   *
   * @return bool
   *   Valor establecido en la configuración para el parámetro
   *   cache_response.
   */
  public function cacheResults() {
    if (!isset($this->cacheResults)) {
      $this->cacheResults = (boolean) $this->getSetting('cache_response');
    }
    return $this->cacheResults;
  }

}
