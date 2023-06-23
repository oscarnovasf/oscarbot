<?php

namespace Drupal\oscarbot_api\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\oscarbot_api\lib\logger\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Operaciones relacionadas con los custom logs.
 */
class LoggerController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Servicio de log.
   *
   * @var \Drupal\oscarbot_api\lib\logger\LoggerInterface
   */
  protected $logger;

  /**
   * Configuración del módulo.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moduleSettings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('oscarbot_api.logger'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructor para inyectar dependencias.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\oscarbot_api\lib\logger\LoggerInterface $logger
   *   Servicio de log.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Servicio de datos de configuración.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory
  ) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
    $this->logger = $logger;
    $this->moduleSettings = $config_factory->get('oscarbot_api.settings');
  }

  /**
   * Muestra los detalles de un log.
   *
   * @param string $type
   *   Tipo de log (server|client).
   * @param int $log_id
   *   Id del log.
   *
   * @return array
   *   Si el ID y tipo se encuentran en la base de datos, se construye un array
   *   con el formato esperado para
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   En caso de que el tipo y el ID no existan.
   */
  public function showLogDetails(string $type, int $log_id): array {
    switch ($type) {

      case 'server':
        $row = $this->logger->loadLogServerData($log_id);
        if ($row) {
          return $this->renderData($row);
        }
        break;

      case 'client':
        /* TODO: Implementar esta funcionalidad copiando la anterior */
        break;

      default:
        throw new NotFoundHttpException($this->t('Not found.'));
    }
  }

  /**
   * Comprueba si se tienen permisos suficientes para ver el log del servidor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Usuario logueado.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessServerLogs(AccountInterface $account) {
    $active_modules = $this->moduleSettings->get('active_modules');
    if (in_array('server', $active_modules) &&
        $account->hasPermission('access oscarbot_api log')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden($this->t('You must enabled server module')->__toString());
  }

  /**
   * Muestra vista de logs del servidor.
   */
  public function showServerLogs() {
    return $this->redirect('view.log_api_server.page');
  }

  /**
   * Obtiene los detalles de un log.
   *
   * @param array $row
   *   Datos del log.
   *
   * @return array
   *   Si el ID y tipo se encuentran en la base de datos, se construye un array
   *   con el formato esperado para
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  private function renderData(array $row) : array {
    $build = [];
    $rows = [];
    $severity = RfcLogLevel::getLevels();

    if ($row) {
      $username = [
        '#theme' => 'username',
        '#account' => $row['uid'] && ($user = $this->userStorage->load($row['uid'])) ? $user : User::getAnonymousUser(),
      ];

      $rows['id'] = [
        'label' => ['data' => $this->t('ID'), 'header' => TRUE],
        'value' => ['data' => $row['id']],
      ];

      $rows['function_name'] = [
        ['data' => $this->t('API Function'), 'header' => TRUE],
        $row['function_name'],
      ];

      $message = $this->formatMessage($row);
      $rows['message'] = [
        ['data' => $this->t('Message'), 'header' => TRUE],
        $message,
      ];

      $rows['severity'] = [
        ['data' => $this->t('Severity'), 'header' => TRUE],
        $severity[$row['severity']],
      ];

      $rows['username'] = [
        ['data' => $this->t('User'), 'header' => TRUE],
        ['data' => $username],
      ];

      $rows['location'] = [
        ['data' => $this->t('Location'), 'header' => TRUE],
        $this->createLink($row['location']),
      ];

      $rows['referer'] = [
        ['data' => $this->t('Referrer'), 'header' => TRUE],
        $this->createLink($row['referer']),
      ];

      $rows['hostname'] = [
        ['data' => $this->t('Hostname'), 'header' => TRUE],
        $row['hostname'],
      ];

      $rows['timestamp'] = [
        ['data' => $this->t('Start date'), 'header' => TRUE],
        (!empty($row['timestamp']) ? $this->dateFormatter->format($row['timestamp'], 'long') : ''),
      ];

      $build['transaction_table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => ['class' => ['custom-log']],
        '#attached' => [
          'library' => ['oscarbot_api/logger'],
        ],
      ];

      $build['data'] = [
        '#type' => 'details',
        '#title' => $this->t('Data'),
        '#open' => FALSE,
      ];

      $data = unserialize($row['data']);
      $build['data']['data_content'] = [
        '#markup' => Markup::create('<pre><code>' . print_r($data, TRUE) . '</code></pre>'),
      ];
    }

    return $build;
  }

  /**
   * Da formato al mensaje almacenado en la base de datos.
   *
   * @param array $row
   *   Contenido de la tabla.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   Mensaje formateado o FALSE si el mensaje o sus variables no están
   *   definidos.
   */
  public function formatMessage(array $row) {
    // Check for required properties.
    if (isset($row['message'], $row['variables'])) {
      $variables = $row['variables'];
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row['message']);
      }
      elseif (!is_array($variables)) {
        $message = $this->t('Log data is corrupted and cannot be unserialized: @message', [
          '@message' => Xss::filterAdmin($row['message']),
        ]);
      }
      // Message to translate with injected variables.
      else {
        // Ensure backtrace strings are properly formatted.
        if (isset($variables['@backtrace_string'])) {
          $variables['@backtrace_string'] = new FormattableMarkup(
            '<pre class="backtrace"><code>@backtrace_string</code></pre>', $variables
          );
        }
        // phpcs:ignore
        $message = $this->t(Xss::filterAdmin($row['message']), $variables);
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Creates a Link object if the provided URI is valid.
   *
   * @param string|null $uri
   *   The uri string to convert into link if valid.
   *
   * @return \Drupal\Core\Link|string|null
   *   Return a Link object if the uri can be converted as a link. In case of
   *   empty uri or invalid, fallback to the provided $uri.
   */
  protected function createLink($uri) {
    if (UrlHelper::isValid($uri, TRUE)) {
      return new Link($uri, Url::fromUri($uri));
    }
    return $uri;
  }

}
