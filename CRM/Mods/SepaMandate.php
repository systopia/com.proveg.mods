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
 * Class CRM_Mods_SepaMandate contains all CiviSEPA modifications
 */
class CRM_Mods_SepaMandate {

  /**
   * This function will generate custom mandate references:
   *
   * For membership and abo payments it will use the membership ID,
   * for other contribution types it would use the contact ID with the prefix 'C'
   *
   * In both cases this is followed by a two-digit squence number
   *
   * Examples
   *  * 234234-03
   *  * C234234-01
   *
   * @param $mandate_parameters array data from hook
   * @throws Exception if no reference could be generated
   */
  public static function createMandateReference(&$mandate_parameters) {
    $contact_id = $mandate_parameters['contact_id'];

    // load contribution (not needed at this point
    if ($mandate_parameters['entity_table']=='civicrm_contribution') {
      $contribution = civicrm_api3('Contribution', 'getsingle', array(
          'id'     => $mandate_parameters['entity_id'],
          'return' => 'financial_type_id'));
    } else if ($mandate_parameters['entity_table']=='civicrm_contribution_recur') {
      $contribution = civicrm_api3('ContributionRecur', 'getsingle', array(
          'id'     => $mandate_parameters['entity_id'],
          'return' => 'financial_type_id'));
    } else {
      throw new Exception("Unsupported mandate type!");
    }

    // if this is a membership payment (Membership Due or Abo) - find the membership number
    $reference = NULL;
    if ($contribution['financial_type_id'] == 8 || $contribution['financial_type_id'] == 2) {
      if (class_exists('CRM_Membership_NumberLogic')) {
        $membership_numbers = CRM_Membership_NumberLogic::getCurrentMembershipNumbers(array($contact_id));
        $reference = CRM_Utils_Array::value($contact_id, $membership_numbers, NULL);
      }
    }

    // if no reference found use contact ID
    if ($reference === NULL) {
      $reference = sprintf('C%07d', $contact_id);
    }

    // find all used references
    $used_references = array();
    $query = CRM_Core_DAO::executeQuery("SELECT reference FROM civicrm_sdd_mandate WHERE reference LIKE '{$reference}-%';");
    while ($query->fetch()) {
      $used_references[] = $query->reference;
    }

    // now find the first sequence that's not taken
    for ($n=1; $n < 100; $n++) {
      $reference_candidate = sprintf("%s-%02d", $reference, $n);
      if (!in_array($reference_candidate, $used_references)) {
        $mandate_parameters['reference'] = $reference_candidate;
        return;
      }
    }

    // if we get here, we ran out of references
    throw new Exception("No more reference available. Change the reference scheme!");
  }

}