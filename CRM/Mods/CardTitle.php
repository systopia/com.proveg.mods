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
 * Class to wrap card title functionality
 */
class CRM_Mods_CardTitle {

  /**
   * Show a warning message to the user if attributes relevant to the card title are to be changed
   *
   * @param $contact_id      integer ID of the contact to be changed
   * @param $contact_changes array   data submitted
   */
  public static function showCardTitleShouldBeAdjustedWarning($contact_id, $contact_changes) {
    // somebody wants to edit a contact, check if it's any of the fields we monitor:
    $fields_to_monitor = ['first_name', 'last_name', 'prefix_id', 'organization_name', 'household_name', 'formal_title'];
    $fields_provided   = array_intersect($fields_to_monitor, array_keys($contact_changes));
    if (!empty($fields_provided) && self::contactHasActiveMembership($contact_id)) {
      // something relevant was submitted. Load the previous data
      try {
        $current_data = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id, 'return' => implode(',', $fields_provided)]);
        foreach ($fields_provided as $field) {
          $current_value = CRM_Utils_Array::value($field, $current_data);
          $future_value  = $contact_changes[$field];
          if ($current_value != $future_value) {
            // there is a change
            CRM_Core_Session::setStatus(E::ts("Don't forget to adjust the membership card title, if necessary."), E::ts("Update Card Title?"), 'warn');
            break; // stop looking, one change is all it needs
          }
        }
      } catch (Exception $ex) {
        Civi::log()->debug("CardTitle check failed: " . $ex->getMessage());
      }
    }
  }

  /**
   * Check if the given contact has a current/active membership (of type "Membership")
   *
   * @param $contact_id  int Contact ID
   * @return bool TRUE iff the contact currently has one or more active memberships
   */
  protected static function contactHasActiveMembership($contact_id) {
    try {
      $count = civicrm_api3('Membership', 'getcount', [
          'contact_id'         => $contact_id,
          'status_id'          => ['IN' => [1, 2, 3, 8]], // New, Current, Grace, Kündigung Ausgesprochen
          'membership_type_id' => 1                       // "Mitglied"
      ]);
      return $count > 0;
    } catch (Exception $ex) {
      Civi::log()->debug("CardTitle: Active membership lookup failed: " . $ex->getMessage());
    }
    return FALSE;
  }

  /**
   * Add the default card title to the membership.create parameters
   *
   * @param $params      array    membership.create parameters
   * @param $contact_id  integer  contact
   */
  public static function addDefaultCardTitle(&$params, $contact_id) {
    $field_id = self::getCardTitleFieldID();
    if ($field_id) {
      $key = "custom_{$field_id}";
      if (empty($params[$key])) {
        $params[$key] = self::calculateDefault($contact_id);
      }
    }
  }

  /**
   * Get card title custom field id
   *
   * @return integer card title field ID
   */
  public static function getCardTitleFieldID() {
    // get field ID (name membership_card_title)
    static $membership_card_title_field_id = NULL;
    if ($membership_card_title_field_id === NULL) {
      try {
        $membership_card_title_field_id = civicrm_api3('CustomField', 'getvalue', ['name' => 'membership_card_title', 'return' => 'id']);
      } catch (Exception $ex) {
        Civi::log()->warning("mods: Error accessing ProVeg Card Title: " . $ex->getMessage());
        $membership_card_title_field_id = 0;
      }
    }
    return $membership_card_title_field_id;
  }

  /**
   * Calculate the default card title for the ProVeg card
   * @param $contact_id integer contact
   * @return string the default title
   */
  public static function calculateDefault($contact_id) {
    $field_list = ['formal_title','first_name','last_name'];
    $contact = civicrm_api3('Contact', 'getsingle', [
        'id'     => $contact_id,
        'return' => implode(',', $field_list) . ',contact_type,display_name']);
    $pieces = [];
    if ($contact['contact_type'] == 'Individual') {
      foreach ($field_list as $field) {
        if (!empty($contact[$field])) {
          $pieces[] = $contact[$field];
        }
      }
    } else {
      $pieces[] = $contact['display_name'];
    }
    return trim(implode(' ', $pieces));
  }
}