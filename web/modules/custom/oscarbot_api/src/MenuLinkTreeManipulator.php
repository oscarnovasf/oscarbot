<?php

declare(strict_types = 1);

namespace Drupal\oscarbot_api;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Routing\AdminContext;

/**
 * Menu link tree manipulator service.
 */
class MenuLinkTreeManipulator extends DefaultMenuLinkTreeManipulators {

  /**
   * The router admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The list of the admin roles.
   *
   * @var string[]
   */
  protected $adminRoles;

  /**
   * Sets the admin context.
   *
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The router admin context service.
   */
  public function setAdminContext(AdminContext $adminContext): void {
    $this->adminContext = $adminContext;
  }

  /**
   * Sets the config service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   */
  public function setConfigFactory(ConfigFactoryInterface $config): void {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $result = parent::menuLinkCheckAccess($instance);

    $plugin_id = $instance->getPluginId();
    $module_settings = $this->config->get('oscarbot_api.settings');
    $active_modules = $module_settings->get('active_modules');

    switch ($plugin_id) {

      case 'custom_module.oscarbot_api.api_swagger_doc':
        if (FALSE == in_array('server', $active_modules) ||
            FALSE == $this->account->hasPermission('access oscarbot_api api doc')) {
          $result = $result->andIf(AccessResult::forbidden()
            ->addCacheContexts(['user.roles']));
        }
        break;

      case 'custom_module.oscarbot_api.reports.log_server':
        if (FALSE == in_array('server', $active_modules) ||
            FALSE == $this->account->hasPermission('access oscarbot_api log')) {
          $result = $result->andIf(AccessResult::forbidden()
            ->addCacheContexts(['user.roles']));
        }
        break;

      case 'custom_module.oscarbot_api.reports.client':
        break;

    }

    return $result;
  }

}
