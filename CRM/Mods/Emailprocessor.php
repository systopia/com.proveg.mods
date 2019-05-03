<?php
/*-------------------------------------------------------+
| proVeg Germany Adjustments                             |
| Copyright (C) 2018 SYSTOPIA                            |
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

/**
 * Implements a custom contact lookup for incoming emails
 */
class CRM_Mods_Emailprocessor {

  /**
   * Implements emailProcessor hook
   *
   * @see PV-8843
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_emailProcessor/
   */
  public static function lookupContact($email, $contactID, &$result) {
    if (!empty($email)) {
      // find all such emails
      $email_query = civicrm_api3('Email', 'get', [
          'email'        => $email,
          'option.limit' => 0,
          'sequential'   => 0,
          'return'       => 'contact_id']);
      $contact_ids = [];
      foreach ($email_query['values'] as $email) {
        $contact_ids[] = (int) $email['contact_id'];
      }
      if (empty($contact_ids)) {
        return; // contact not found
      }

      // try to find an organisation
      $org_query = civicrm_api3('Contact', 'get', [
          'contact_type' => 'Organization',
          'id'           => ['IN' => $contact_ids],
          'is_deleted'   => 0,
          'sequential'   => 0,
          'return'       => 'id']);
      if ($org_query['count'] == 1) {
        // we have a winner!
        $result = [
            'contactID' => $org_query['id'],
            'action'    => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE,
        ];
        return;
      } elseif ($org_query['count'] > 1) {
        // use oldest ID (see https://projekte.systopia.de/redmine/issues/8843#note-7)
        $result = [
            'contactID' => min(array_keys($org_query['values'])),
            'action'    => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE,
        ];
        return;
      }

      // finally: let's find a person
      $ind_query = civicrm_api3('Contact', 'get', [
          'contact_type' => 'Individual',
          'id'           => ['IN' => $contact_ids],
          'is_deleted'   => 0,
          'sequential'   => 0,
          'return'       => 'id']);
      if ($ind_query['count'] == 1) {
        // we have a winner!
        $result = [
            'contactID' => $ind_query['id'],
            'action'    => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE,
        ];
        return;
      } elseif ($ind_query['count'] > 1) {
        // use oldest ID (see https://projekte.systopia.de/redmine/issues/8843#note-7)
        $result = [
            'contactID' => min(array_keys($ind_query['values'])),
            'action'    => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE,
        ];
        return;
      }
    }
  }
}