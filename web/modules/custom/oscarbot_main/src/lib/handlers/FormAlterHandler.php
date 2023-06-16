<?php

namespace Drupal\oscarbot_main\lib\handlers;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Conjunto de Handlers usados en el hook form_alter principalmente.
 */
class FormAlterHandler {

  use StringTranslationTrait;

  /**
   * Añade placeholders a los formularios de usuario.
   *
   * Login, registro de usuario, recuperar contraseña...
   *
   * @param array $form
   *   Array con el formulario.
   * @param string $form_id
   *   Cadena con el identificador del formulario.
   *
   * @return array
   *   Array con el formulario y los placeholders añadidos.
   */
  public static function addPlaceholderUserForm(array $form, string $form_id): array {
    $forms_users_add_placeholder = [
      'user_login_form',
      'user_register_form',
      'user_pass',
    ];

    if (TRUE === in_array($form_id, $forms_users_add_placeholder)) {
      if (isset($form['name'])) {
        $form['name']['#attributes']['placeholder'] = $form['name']['#title'];
      }
      if (isset($form['pass'])) {
        $form['pass']['#attributes']['placeholder'] = $form['pass']['#title'];
      }
      if (isset($form['mail']) && isset($form['mail']['#title'])) {
        $form['mail']['#attributes']['placeholder'] = $form['mail']['#title'];
      }
    }

    return $form;
  }

}
