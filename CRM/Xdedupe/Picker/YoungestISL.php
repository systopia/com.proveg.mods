<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Implement a "ContactPicker", i.e. a class that will identify the main contact from a list of contacts
 */
class CRM_Xdedupe_Picker_YoungestISL extends CRM_Xdedupe_Picker {

  static $isl_pattern = '/^ISL-(?<isl_number>[0-9]+)$/';
  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Youngest (by ISL ID)");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("If all contacts have an ISL-XXXX external identifier, it picks the contact with the highest ID");
  }

  /**
   * Select the main contact from a set of contacts
   *
   * @param $contact_ids array list of contact IDs
   * @return int|null one of the contacts in the list. null means "can't decide"
   */
  public function selectMainContact($contact_ids) {
    // load all external IDs
    $contacts = civicrm_api3('Contact', 'get', [
        'id'           => ['IN' => $contact_ids],
        'option.limit' => 0,
        'return'       => 'id,external_identifier'
    ]);

    // have a look at all of them
    $max_isl_number  = 0;
    $main_contact_id = NULL;
    foreach ($contacts['values'] as $contact) {
      $external_identifier = CRM_Utils_Array::value('external_identifier', $contact, '');
      if (preg_match(self::$isl_pattern, $external_identifier, $match)) {
        $current_isl_number = (int) $match['isl_number'];
        if ($current_isl_number > $max_isl_number) {
          $max_isl_number = $current_isl_number;
          $main_contact_id = $contact['id'];
        }
      } else {
        // this is not a ISL-xxx identifier, abort!
        return NULL;
      }
    }

    return $main_contact_id;
  }
}
