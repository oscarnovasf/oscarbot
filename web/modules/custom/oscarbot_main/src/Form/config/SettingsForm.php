<?php

namespace Drupal\oscarbot_main\Form\config;

/**
 * @file
 * SettingsForm.php
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Extension\ExtensionPathResolver;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\oscarbot_main\lib\general\MarkdownParser;

/**
 * Formulario de configuración del módulo.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Undocumented variable
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Constructor para añadir dependencias.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $logger
   *   Servicio PathResolver.
   */
  public function __construct(ExtensionPathResolver $path_resolver) {
    $this->pathResolver = $path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.path.resolver'),
    );
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'oscarbot_main.settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['oscarbot_main.settings'];
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* Obtengo la configuración actual */
    /* $config = \Drupal::configFactory()->getEditable('custom_module.oscarbot_main.settings'); */
    $config = $this->config('oscarbot_main.settings');

    /* SETTINGS FORM */
    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
      '#group' => 'settings',
      '#description' => $this->t('<p><h2>General Settings</h2></p>'),
    ];

    /* *************************************************************************
     * INFORMACIÓN, LICENCIA y AYUDA: CONTENIDO DE CHANGELOG.md, LICENSE.md
     * y README.md
     * ************************************************************************/

    /* Datos auxiliares */
    $module_path = $this->pathResolver->getPath('module', "oscarbot_main");
    $parser = new MarkdownParser();

    /* Templates */
    $info_template = file_get_contents($module_path . "/templates/custom/info.html.twig");
    $help_template = file_get_contents($module_path . "/templates/custom/help.html.twig");
    $license_template = file_get_contents($module_path . "/templates/custom/license.html.twig");

    /* Compruebo si existe y leo el contenido del archivo CHANGELOG.md */
    $changelog_ruta = $module_path . "/CHANGELOG.md";
    $contenido = '';
    if (file_exists($changelog_ruta)) {
      $contenido = file_get_contents($changelog_ruta);
      $contenido = Markup::create($parser->text($contenido));
    }

    if ($contenido) {
      $form['info'] = [
        '#type' => 'details',
        '#title' => $this->t('Info'),
        '#group' => 'settings',
        '#description' => '',
      ];

      $form['info']['info'] = [
        '#type' => 'inline_template',
        '#template' => $info_template,
        '#context' => [
          'changelog' => $contenido,
        ],
      ];
    }

    /* Compruebo si existe y leo el contenido del archivo LICENSE.md */
    $license_ruta = $module_path . "/LICENSE.md";
    $contenido = '';
    if (file_exists($license_ruta)) {
      $contenido = file_get_contents($license_ruta);
      $contenido = Markup::create($parser->text($contenido));
    }

    if ($contenido) {
      $form['license'] = [
        '#type' => 'details',
        '#title' => $this->t('License'),
        '#group' => 'settings',
        '#description' => '',
      ];

      $form['license']['license'] = [
        '#type' => 'inline_template',
        '#template' => $license_template,
        '#context' => [
          'license' => $contenido,
        ],
      ];
    }

    /* Compruebo si existe y leo el contenido del archivo README.md */
    $readme_ruta = $module_path . "/README.md";
    $contenido = '';
    if (file_exists($readme_ruta)) {
      $contenido = file_get_contents($readme_ruta);
      $contenido = Markup::create($parser->text($contenido));
    }

    if ($contenido) {
      $form['help'] = [
        '#type' => 'details',
        '#title' => $this->t('Help'),
        '#group' => 'settings',
        '#description' => '',
      ];

      $form['help']['help'] = [
        '#type' => 'inline_template',
        '#template' => $help_template,
        '#context' => [
          'readme' => $contenido,
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

    $config = $this->config('oscarbot_main.settings');

    /* TODO: Indicar todos los campos a guardar */
    $list = [];

    foreach ($list as $item) {
      $config->set($item, $form_state->getValue($item));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
