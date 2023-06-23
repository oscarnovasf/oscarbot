<?php

namespace Drupal\oscarbot_api\lib;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use GuzzleHttp\ClientInterface;

use Drupal\oscarbot_api\lib\logger\LoggerInterface;

// phpcs:ignore
use Exception;

/**
 * Defines la base para las peticiones Rest al API.
 */
abstract class RestClientBase extends RestBase {

  use StringTranslationTrait;

  /**
   * Idiomas que admite el API.
   */
  const API_LANGUAGES = ['ES', 'EN'];

  /**
   * Interfaz para el idioma.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Cliente http.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Construye una nueva instancia del servicio.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Cliente http.
   * @param \Drupal\oscarbot_api\lib\logger\LoggerInterface $logger
   *   Custom logger.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    ClientInterface $http_client,
    LoggerInterface $logger
  ) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('oscarbot_api.settings');
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;

    parent::__construct($logger);
  }

  /**
   * Realiza una petición GET.
   *
   * @param string $action
   *   Nombre de la función que se desea llamar.
   * @param array $parameters
   *   Array con los parámetros necesarios para la función.
   *
   * @return array|false
   *   Array con la respuesta. FALSE en caso de error.
   */
  public function get(string $action, array $parameters = []) {
    return $this->getResponse('GET', $action, $parameters);
  }

  /**
   * Realiza una petición POST.
   *
   * @param string $action
   *   Nombre de la función que se desea llamar.
   * @param array $parameters
   *   Array con los parámetros necesarios para la función.
   *
   * @return array|false
   *   Array con la respuesta. FALSE en caso de error.
   */
  public function post(string $action, array $parameters = []) {
    return $this->getResponse('POST', $action, $parameters);
  }

  /**
   * Realiza una petición PUT.
   *
   * @param string $action
   *   Nombre de la función que se desea llamar.
   * @param array $parameters
   *   Array con los parámetros necesarios para la función.
   *
   * @return array|false
   *   Array con la respuesta. FALSE en caso de error.
   */
  public function put(string $action, array $parameters = []) {
    return $this->getResponse('PUT', $action, $parameters);
  }

  /**
   * Realiza una petición DELETE.
   *
   * @param string $action
   *   Nombre de la función que se desea llamar.
   * @param array $parameters
   *   Array con los parámetros necesarios para la función.
   *
   * @return array|false
   *   Array con la respuesta. FALSE en caso de error.
   */
  public function delete(string $action, array $parameters = []) {
    return $this->getResponse('DELETE', $action, $parameters);
  }

  /**
   * Obtiene la respuesta del API.
   *
   * @param string $method
   *   El método http (GET, POST, ...).
   * @param string $action
   *   Función del API que se quiere llamar.
   * @param array $parameters
   *   Array con los parámetros requeridos por la función.
   *
   * @return array|false
   *   Array con la respuesta. FALSE en caso de error.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function getResponse(string $method,
                                 string $action,
                                 array $parameters = []) {
    /* Limpio posibles errores anteriores */
    $this->clearLastErrorMessage();

    /* Añado el idioma de la interfaz del usuario que realiza la petición */
    $langcode = strtoupper($this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId());
    if (!in_array($langcode, self::API_LANGUAGES)) {
      $langcode = 'ES';
    }

    /* Construyo la petición y los argumentos */
    $uri = $this->getSetting('base_url') . '/' . $action;
    $options = [
      'http_errors' => FALSE,
    ];

    /* Compruebo si debo ignorar la verificación SSL */
    $skip_ssl = $this->getSetting('skip_ssl');
    if ($skip_ssl) {
      $options['verify'] = FALSE;
    }

    $api_key = $this->getSetting('api_key');
    if ($api_key) {
      /* Compruebo el tipo de api que estoy usando */
      $api_key_type = $this->getSetting('api_key_type');
      switch ($api_key_type) {

        case 'get':
          $parameters['token'] = $api_key;
          break;

        case 'header':
          $options['headers']['X-Backend-Gateway-Token'] = $api_key;
          break;

        case 'basic':
          $access_data = explode(':', $api_key);
          if (is_array($access_data)) {
            $options['auth'][] = $access_data[0] ?? '';
            $options['auth'][] = $access_data[1] ?? '';
          }
          break;
      }
    }

    /* Compruebo conexión vía proxy */
    $proxy_server = $this->getSetting('proxy_server');
    if ($proxy_server) {
      $options['proxy'] = $proxy_server;
    }

    /* Añado los parámetros a la url o al body */
    $send_params_type = $this->getSetting('send_params_type');
    if (!empty($parameters)) {
      switch ($send_params_type) {

        case 'get':
          $uri .= '?' . http_build_query($parameters);
          break;

        case 'body_json':
          $options['headers']['Content-Type'] = 'application/json';
          $options['body'] = Json::encode($parameters);
          break;
      }
    }

    /* Ejecuto la petición con seguimiento de excepciones. */
    try {
      Timer::start(self::MODULE_NAME);
      $response = $this->httpClient->request($method, $uri, $options);
      if ($this->performaceTracing()) {
        $this->logger()->notice('<pre><code>' . print_r($this->var2str($response), TRUE) . '</code></pre>');
      }
      if (empty($response)) {
        throw new Exception('Request with empty response');
      }
      $status_code = $response->getStatusCode();
      if (empty($status_code)) {
        throw new Exception('Request with no status code');
      }
      if (!($response_body = $response->getBody()->getContents())) {
        $response_body = [];
      }
      else {
        $response_body = Json::decode($response_body, TRUE);
      }
      Timer::stop(self::MODULE_NAME);
    }
    catch (\Exception $e) {
      Timer::stop(self::MODULE_NAME);
      $message = [
        'action' => $method . '(' . $action . ')',
        'options' => $options,
        'uri' => $uri,
        'status_code' => method_exists($e, 'getCode') ? $e->getCode() : 'XXX',
        'langcode' => $langcode,
        'execution_time' => round(Timer::read(self::MODULE_NAME) / 1000, 3),
        'parameters' => $this->var2str($parameters),
        'error_info' => (method_exists($e, 'getMessage') ? $this->var2str($e->getMessage()) : 'Unexpected error'),
      ];
      $this->logger()->error('<pre><code>' . print_r($message, TRUE) . '</code></pre>');
      return FALSE;
    }

    /* Compruebo posibles errores en el resultado de la petición */
    if ($status_code != 200 && empty($response_body)) {
      $response_body['errors'] = '???';
    }

    /* Compruebo posibles errores devueltos por el api */
    $has_errors = FALSE;
    $error_message = '';
    $error_description = (!empty($response_body['errors']) ? $response_body['errors'][0] : FALSE);
    if ($error_description !== FALSE) {
      $has_errors = TRUE;
      $error_message = 'Error';
      if (!empty($error_description)) {
        $error_message .= ': ' . $this->setLastErrorMessage($error_description);
      }
    }

    /* Guardo en el log de Drupal posibles errores devueltos por el api */
    if (!empty($error_message) && $this->trackErrorResult()) {
      $severity = ($has_errors ? 'error' : 'notice');
      $message = [
        'action' => $method . '(' . $action . ')',
        'status_code' => $status_code,
        'langcode' => $langcode,
        'execution_time' => round(Timer::read(self::MODULE_NAME) / 1000, 3),
        'parameters' => $this->var2str($parameters),
        'error_info' => $error_message,
        'response' => (!empty($response_body) ? $this->var2str($response_body) : '(empty)'),
      ];
      $this->logger()->{$severity}('<pre><code>' . print_r($message, TRUE) . '</code></pre>');
      if (!$has_errors) {
        // Return any data provided by the service or just TRUE to allow the
        // caller know that no critical errors have been detected.
        return ($response_body ?? TRUE);
      }
      return FALSE;
    }

    /* Guardo información de la petición en el log de Drupal */
    if ($this->performaceTracing()) {
      $message = [
        'action' => $method . '(' . $action . ')',
        'status_code' => $status_code,
        'langcode' => $langcode,
        'execution_time' => round(Timer::read(self::MODULE_NAME) / 1000, 3),
        'parameters' => $this->var2str($parameters),
        'response' => (!empty($response_body) ? $this->var2str($response_body) : '(empty)'),
      ];
      $this->logger()->info('<pre><code>' . print_r($message, TRUE) . '</code></pre>');
    }

    return ($response_body ?? TRUE);
  }

}
