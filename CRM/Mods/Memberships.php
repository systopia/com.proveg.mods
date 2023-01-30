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

/**
 * Class to wrap membership functions
 */
class CRM_Mods_Memberships {

  const FEE_TYPE_FIELD = 'custom_27'; // adjust if needed

  /**
   * General new membership post processing
   *
   * @param $membership_id          int membership ID
   * @param $contact_id             int contact ID
   * @param $contribution_recur_id  int contribution recur ID
   * @param $ui_present             boolean is there a user with an UI
   */
  public static function newMembershipPostprocess($membership_id, $contact_id, $contribution_recur_id, $ui_present) {
    // load current data
    $membership = civicrm_api3('Membership', 'getsingle', [
        'id'     => $membership_id,
        'return' => 'id,start_date,' . CRM_Mods_Memberships::FEE_TYPE_FIELD,
    ]);
    if ($contribution_recur_id) {
      $recurring_contribution = civicrm_api3('ContributionRecur', 'getsingle', ['id' => $contribution_recur_id]);
    }

    // collect all updates in one set
    $membership_update = [
        'id' => $membership_id
    ];

    // adjust start date
    $membership_update['start_date'] = self::calculateStartDate($membership['start_date']);
    $membership_update['end_date'] = self::calculateEndDate($membership_update['start_date']);

    // add annual amount
    try {
      // fixme: should be using P60Membership code
      if (isset($recurring_contribution)) {
        $annual_field_id = civicrm_api3('CustomField', 'getvalue', ['name' => 'membership_annual', 'return' => 'id']);
        $every_n_months = $recurring_contribution['frequency_interval'];
        if ($recurring_contribution['frequency_unit'] == 'year') {
          $every_n_months *= 12;
        }
        $membership_update["custom_{$annual_field_id}"] = ((float) $recurring_contribution['amount']) * 12.0 / (float) $every_n_months;
      }
    } catch (Exception $ex) {
      if ($ui_present) {
        CRM_Core_Session::setStatus(E::ts("Custom field for annual membership fee not found"), E::ts("Custom Field Not Found"), 'warning');
      } else {
        Civi::log()->debug("MembershipPostprocess: " . E::ts("Custom field for annual membership fee not found"));
      }
    }

    // add card title
    CRM_Mods_CardTitle::addDefaultCardTitle($membership_update, $contact_id);

    // set fee type (#27) to '1' (Individual)
    if (empty($membership[CRM_Mods_Memberships::FEE_TYPE_FIELD])) {
      $membership_update[CRM_Mods_Memberships::FEE_TYPE_FIELD] = 1;
    }

    // write membership update
    civicrm_api3('Membership', 'create', $membership_update);

    // adjust mandate start date
    if (isset($recurring_contribution)) {
      $mandate = civicrm_api3('SepaMandate', 'get', [
          'entity_id'    => $recurring_contribution['id'],
          'entity_table' => 'civicrm_contribution_recur',
          'option.limit' => 1]);
      if (!empty($mandate['id'])) {
        // this is a SEPA mandate
        civicrm_api3('ContributionRecur', 'create', [
            'id'         => $recurring_contribution['id'],
            'start_date' => $membership_update['start_date']
        ]);
      }

      // assign sepa mandate to membership
      CRM_Membership_PaidByLogic::getSingleton()->changeContract($membership_id, $contribution_recur_id);
    }
  }

  /**
   * determine the mandate/membership start date
   */
  public static function calculateStartDate($init_date) {
    // make sure it's at least 14 days from now
    $start_date = max(strtotime($init_date), strtotime("now + 10 days"));

    // move forward until 1st of month
    while (date('j', $start_date) > 1) {
      // get to the next day
      $start_date = strtotime("+1 day", $start_date);
    }
    return date('Y-m-d', $start_date);
  }

    /**
     * Calculates the membership end date for a given start date.
     *
     * @param string $start_date
     *   A date/time string parseable by strtotime().
     *
     * @return string
     *   The membership end date formatted as "Y-m-d".
     */
  public static function calculateEndDate($start_date) {
      $end_date = strtotime("{$start_date} +1 year -1 day");
      return date('Y-m-d', $end_date);
  }


  /**
   * Check if the given contact has a current/active membership (of type "Membership")
   *
   * @param $contact_id  int Contact ID
   * @return bool TRUE iff the contact currently has one or more active memberships
   */
  public static function contactHasActiveMembership($contact_id) {
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


}