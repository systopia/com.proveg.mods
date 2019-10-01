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