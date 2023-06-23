<?php

namespace Drupal\oscarbot_api\Form\config;

/**
 * @file
 * SettingsForm.php
 */

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración del módulo.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'oscarbot_api_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['oscarbot_api.settings'];
  }

  /**
   * Implements buildForm().
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* Obtengo la configuración actual */
    /* $config = \Drupal::configFactory()->getEditable('oscarbot_api.settings'); */
    $config = $this->config('oscarbot_api.settings');

    /* SETTINGS FORM */
    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    /* CONFIGURACIÓN GENERAL */
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
      '#group' => 'settings',
      '#description' => $this->t('<p><h2>General Settings</h2></p>'),
    ];

    $form['general_settings']['activate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Activate'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['general_settings']['activate']['active_modules'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Modules'),
      '#required' => TRUE,
      '#access' => TRUE,
      '#options' => [
        'client' => $this->t('Client'),
        'server' => $this->t('Server'),
      ],
    ];
    if ($config->get('active_modules')) {
      $form['general_settings']['activate']['active_modules']['#default_value'] = $config->get('active_modules');
    }

    /* CONFIGURACIÓN DEL CLIENTE */
    $form['client_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Client'),
      '#open' => FALSE,
      '#group' => 'settings',
      '#description' => $this->t('<p><h2>Client Settings</h2></p>'),
    ];

    $form['client_settings']['info_client'] = [
      '#type' => 'item',
      '#markup' => $this->t('You must activate the client module.'),
      '#states' => [
        'visible' => [
          '#edit-active-modules-client' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['client_settings']['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          '#edit-active-modules-client' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['client_settings']['connection']['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Base URL'),
      '#description' => $this->t('Does not add the / end of the url that includes the http(s)'),
      '#default_value' => $config->get('base_url', ''),
      '#access' => TRUE,
      '#states' => [
        'required' => [
          '#edit-active-modules-client' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['client_settings']['connection']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key', ''),
      '#access' => TRUE,
    ];

    /* Tipo de envío de la clave api */
    $form['client_settings']['connection']['api_key_type'] = [
      '#type' => 'select',
      '#title' => $this->t('API key type'),
      '#default_value' => $config->get('api_key_type', '_none'),
      '#access' => TRUE,
      '#options' => [
        'get' => 'GET (token)',
        'header' => 'Header (X-Backend-Gateway-Token)',
        'basic' => 'Authorization Basic',
      ],
      '#empty_option' => $this->t('-- None --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
    ];

    /* Opciones para enviar los parámetros a las peticiones */
    $form['client_settings']['connection']['send_params_type'] = [
      '#type' => 'select',
      '#title' => $this->t('API key type'),
      '#default_value' => $config->get('send_params_type', '_none'),
      '#access' => TRUE,
      '#options' => [
        'get' => 'GET',
        'body_json' => 'Body (json)',
      ],
      '#empty_option' => $this->t('-- None --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
    ];

    $form['client_settings']['proxy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Proxy'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          '#edit-active-modules-client' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['client_settings']['proxy']['proxy_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy server'),
      '#description' => $this->t('Example: http://username:password@192.168.16.1:10'),
      '#default_value' => $config->get('proxy_server', ''),
      '#access' => TRUE,
    ];

    /* CONFIGURACIÓN DEL SERVIDOR */
    $form['server_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Server'),
      '#open' => FALSE,
      '#group' => 'settings',
      '#description' => $this->t('<p><h2>Server Settings</h2></p>'),
    ];

    $form['server_settings']['info_server'] = [
      '#type' => 'item',
      '#markup' => $this->t('You must activate the server module.'),
      '#states' => [
        'visible' => [
          '#edit-active-modules-server' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['server_settings']['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          '#edit-active-modules-server' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['server_settings']['connection']['backend_gateway_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backend Gateway Token'),
      '#description' => $this->t('Remote access token (X-Backend-Gateway-Token)'),
      '#default_value' => $config->get('backend_gateway_token', ''),
      '#access' => TRUE,
      '#states' => [
        'required' => [
          '#edit-active-modules-server' => ['checked' => TRUE],
        ],
      ],
    ];

    /* OPCIONES DE DESARROLLO */
    $form['devel_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Devel'),
      '#open' => FALSE,
      '#group' => 'settings',
      '#description' => $this->t('<p><h2>Devel Settings</h2></p>'),
    ];

    $form['devel_settings']['log'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Log options'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['devel_settings']['log']['info'] = [
      '#markup' => $this->t('<small>Info: Exceptions produced in API calls are automatically stored.</small>'),
    ];

    $form['devel_settings']['log']['performance_tracing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled LOG'),
      '#default_value' => $config->get('performance_tracing', FALSE),
      '#required' => FALSE,
      '#access' => TRUE,
    ];

    $form['devel_settings']['log']['track_error_result'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save errors returned by the API in the Drupal log.'),
      '#default_value' => $config->get('track_error_result', FALSE),
      '#required' => FALSE,
      '#access' => TRUE,
    ];

    /* MANTENIMIENTO */
    $form['devel_settings']['log']['maintenance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Maintenance'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['devel_settings']['log']['maintenance']['delete_log'] = [
      '#title' => $this->t('Delete logs'),
      '#type' => 'link',
      '#url' => Url::fromRoute('custom_module.oscarbot_api.reports.delete'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
        ],
      ],
    ];

    $form['devel_settings']['development'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Development options'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['devel_settings']['development']['skip_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip SSL certificate problem: self signed certificate in certificate chain'),
      '#default_value' => $config->get('skip_ssl', FALSE),
      '#required' => FALSE,
      '#access' => TRUE,
    ];

    /* *************************************************************************
     * INFORMACIÓN, LICENCIA y AYUDA: CONTENIDO DE CHANGELOG.md, LICENSE.md
     * y README.md
     * ************************************************************************/

    $module_path = \Drupal::service('extension.path.resolver')
      ->getPath('module', "oscarbot_api");

    /* *************************************************************************
     * MANUAL TÉCNICO.
     * ************************************************************************/
    $manual_ruta = $module_path . "/docs/manual/index.html";
    if (file_exists($manual_ruta)) {
      $form['manual'] = [
        '#type' => 'details',
        '#title' => $this->t('Manual'),
        '#group' => 'settings',
        '#description' => '',
      ];

      $form['manual']['manual-info'] = [
        '#type' => 'details',
        '#title' => $this->t('Technical manual'),
        '#open' => TRUE,
      ];

      $form['manual']['manual-info']['manual-html'] = [
        '#type' => 'inline_template',
        '#template' => '<iframe width="100%" style="overflow:hidden;height:650px;width:100%" height="100%" src="/{{ url }}"></iframe>',
        '#context' => [
          'url' => $manual_ruta,
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('oscarbot_api.settings');

    /* Todos los campos a guardar */
    $list = [
      'active_modules',
      'base_url',
      'api_key',
      'api_key_type',
      'send_params_type',
      'proxy_server',
      'backend_gateway_token',
      'performance_tracing',
      'track_error_result',
      'skip_ssl',
    ];

    foreach ($list as $item) {
      $config->set($item, $form_state->getValue($item));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
