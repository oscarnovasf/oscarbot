<?php

namespace Drupal\oscarbot_api\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\oscarbot_api\lib\logger\Logger;

/**
 * Formulario para la confirmación de la eliminación de logs.
 */
class ConfirmDeleteLogsForm extends ConfirmFormBase {

  /**
   * Total de eventos del servidor que serán eliminados.
   *
   * @var int
   *   Total.
   */
  private $totalServerLogs = "";

  /**
   * Total de eventos del cliente que serán eliminados.
   *
   * @var int
   *   Total.
   */
  private $totalClientLogs = "";

  /**
   * Servicio de logger.
   *
   * @var \Drupal\oscarbot_api\lib\logger\LogLoggerInterface
   */
  protected $logger;

  /**
   * Servicio de mensajes.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructor para inyectar dependencias.
   *
   * @param \Drupal\oscarbot_api\lib\logger\Logger $logger
   *   Servicio de logger.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Servicio de mensajes.
   */
  public function __construct(Logger $logger,
                              Messenger $messenger) {
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oscarbot_api.logger'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "oscarbot_api_confirm_delete_logs_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* Primero genero el formulario por defecto */
    $form = parent::buildForm($form, $form_state);

    /* Obtengo las cantidades de elementos que se van a eliminar */
    $this->totalServerLogs = $this->logger->getTotalLogServerData();
    $this->totalClientLogs = $this->logger->getTotalLogClientData();

    $form['total_operaciones'] = [
      '#markup' => $this->t('@total server logs will be eliminated', [
        '@total' => $this->totalServerLogs,
      ]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    $form['total_operaciones_log'] = [
      '#markup' => $this->t('@total client logs will be deleted', [
        '@total' => $this->totalClientLogs,
      ]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($this->totalServerLogs + $this->totalClientLogs > 0) {
      $result = $this->logger->borrarLogs();
      $this->messenger()->addMessage($this->t('@logs_servers server logs and @logs_client client logs records have been deleted', [
        '@logs_servers' => $result['num_events_server'],
        '@logs_client'  => $result['num_events_client'],
      ]));
    }
    else {
      /* Mostrar mensaje de ninguna operación a realizar. */
      $this->messenger()->addWarning($this->t('No records have been deleted'));
    }

    return $this->returnToCallerPage()->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute("custom_module.oscarbot_api.settings", [], ['fragment' => 'edit-devel-settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /* Construyo el mensaje (pregunta) de cabecera */
    $mensaje = $this->t('Delete logs');

    return $mensaje;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /* Vacío la descripción del formulario pues ya se muestra en buildForm */
    return '';
  }

  /* ***************************************************************************
   * MÉTODOS PRIVADOS.
   * ************************************************************************ */

  /**
   * Función returnToCallerPage().
   *
   * Devuelve una redirección a la página que llamó al procedimiento.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirección.
   */
  private function returnToCallerPage() {
    $url = Url::fromRoute("custom_module.oscarbot_api.settings", [], ['fragment' => 'edit-devel-settings']);
    return new RedirectResponse($url->toString());
  }

}
