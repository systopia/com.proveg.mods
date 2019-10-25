<?php
/*-------------------------------------------------------+
| proVeg Germany Adjustments                             |
| Copyright (C) 2019 SYSTOPIA                            |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Mods_ExtensionUtil as E;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Civi\Core\Event\GenericHookEvent;

/**
 * Implement the general configuration
 */
class CRM_Xdedupe_ProVeg  implements EventSubscriberInterface {

  /**
   * Subscribe to the list events, so we can plug the built-in ones
   */
  public static function getSubscribedEvents() {
    return [
        'civi.xdedupe.finders'   => ['addBuiltinFinders',   Events::W_MIDDLE],
        'civi.xdedupe.filters'   => ['addBuiltinFilters',   Events::W_MIDDLE],
        'civi.xdedupe.resolvers' => ['addBuiltinResolvers', Events::W_MIDDLE],
        'civi.xdedupe.pickers'   => ['addBuiltinPickers',   Events::W_MIDDLE],
    ];
  }

  /**
   * Return the list of built-in finders
   */
  public function addBuiltinFinders(GenericHookEvent $xdedupe_list) {
    $xdedupe_list->list = array_merge($xdedupe_list->list, [
    ]);
  }

  /**
   * Return the list of built-in filters
   */
  public function addBuiltinFilters(GenericHookEvent $xdedupe_list) {
    $xdedupe_list->list = array_merge($xdedupe_list->list, [
    ]);
  }

  /**
   * Return the list of built-in resolvers
   */
  public function addBuiltinResolvers(GenericHookEvent $xdedupe_list) {
    $xdedupe_list->list = array_merge($xdedupe_list->list, [
        'CRM_Xdedupe_Resolver_WantsDonationReceipt'
    ]);
  }

  /**
   * Return the list of built-in pickers
   */
  public function addBuiltinPickers(GenericHookEvent $xdedupe_list) {
    $xdedupe_list->list = array_merge($xdedupe_list->list, [
        'CRM_Xdedupe_Picker_YoungestISL',
    ]);
  }
}
