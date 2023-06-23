<?php

namespace Drupal\oscarbot_api\lib\Traits;

use Drupal\Core\Url;

/**
 * Conjunto de funciones globales.
 */
trait GlobalTrait {

  /**
   * Realiza un barrido en la variable para truncar los valores.
   *
   * Recorre el parámetro recibido buscando todas las cadenas superiores a
   * 80 caracteres y las sustituye por una cadena que muestra el tamaño de la
   * original. Con esto se evita logs ilegibles por exceso de tamaño.
   *
   * También convierte a string los valores boleanos y, si se trata de un
   * objeto Url lo convierte a string.
   *
   * @param mixed $variable
   *   Variable a convertir/validar.
   *
   * @return mixed
   *   La misma variable ya convertida/validada.
   */
  public function var2str($variable) {
    if (is_string($variable)) {
      $length = strlen($variable);
      if ($length > 80) {
        $variable = '...(' . $length . ')...';
      }
      return $variable;
    }
    elseif (is_array($variable)) {
      foreach ($variable as $key => &$value) {
        $value = self::var2str($value);
      }
    }
    elseif (is_bool($variable)) {
      $variable = $variable ? 'true' : 'false';
    }
    elseif ($variable instanceof Url) {
      $variable = $variable->toString();
    }

    return $variable;
  }

}
