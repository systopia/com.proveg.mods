<?php
/*-------------------------------------------------------+
| proVeg Germany Adjustments                             |
| Copyright (C) 2020 SYSTOPIA                            |
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

class CRM_Mods_MailingEventConfirmWrapper implements API_Wrapper {

  /**
   * @param array $apiRequest
   *
   * @return array|void
   */
  public function fromApiInput($apiRequest) {
    if (!isset($apiRequest['params']['contact_id']) || !isset($apiRequest['params']['subscribe_id']) || !isset($apiRequest['params']['hash'])) {
      Civi::log()->debug("[CRM_Mods_MailingEventConfirmWrapper] Missing parameters. Not logging Request.");
      return $apiRequest;
    }
    $contact_id = $apiRequest['params']['contact_id'];
    $subscribe_event_id = $apiRequest['params']['subscribe_id'];
    $hash = $apiRequest['params']['hash'];

    // Shamelessly stolen from CRM/Mailing/Event/BAO/Confirm.php
    $se = &CRM_Mailing_Event_BAO_Subscribe::verify(
      $contact_id,
      $subscribe_event_id,
      $hash
    );
    if (!$se) {
      Civi::log()->debug("[CRM_Mods_MailingEventConfirmWrapper] Event not found. Not logging request.");
      return $apiRequest;
    }
    $group_id = $se->group_id;
    $logger = new CRM_Mods_SubscriptionLogger($contact_id, $hash, $group_id);
    $logger->log_subscription('MailingEventConfirm');

    return $apiRequest;
  }

  /**
   * Interface for interpreting api output.
   *
   * @param array $apiRequest
   * @param array $result
   *
   * @return array
   *   modified $result
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }
}