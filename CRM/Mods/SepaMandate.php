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

  /** list of German holidays with a fixed date */
  protected static $STATIC_HOLIDAYS = array('01-01', '05-01', '10-03', '12-25', '12-26', '12-31');

  /** list of Easter Sunday dates */
  protected static $EASTER_SUNDAYS = array('2018-04-01', '2019-04-21', '2020-04-12', '2021-04-04', '2022-04-14', '2023-04-09', '2024-03-31', '2025-04-20', '2026-04-05', '2027-03-28');

  /** there are 4 easter related days: -2 (Good Friday), +1 (Easter Monday), +39 (Ascension Day), +50 (Whit Monday) */
  protected static $EASTER_HOLIDAYS = array('-2', '+1', '+39', '+50');

  /** cach for financial types */
  protected static $FINANCIAL_TYPE_NAMES = NULL;



  /**
   * Check if this a valid day to collect SEPA direct debits
   *
   * @param $date string date
   * @return boolean iff this is a valid collection date
   */
  public static function is_collection_day($collection_date) {
    // check for weekends:
    $day_of_week = date('N', strtotime($collection_date));
    if ($day_of_week > 5) {
      return FALSE;
    }

    // check for (German) static holidays
    $date = substr($collection_date, 5);
    if (in_array($date, self::$STATIC_HOLIDAYS)) {
      return FALSE;
    }

    // check for the non-static holidays (easter-based)
    // first: find easter sunday
    $year = substr($collection_date, 0, 4);
    $easter_sunday = NULL;
    foreach (self::$EASTER_SUNDAYS as $easter_sunday_candidate) {
      if ($year == substr($easter_sunday_candidate, 0, 4)) {
        $easter_sunday = $easter_sunday_candidate;
        break;
      }
    }
    if ($easter_sunday) {
      // check the holidays related to easter
      foreach (self::$EASTER_HOLIDAYS as $holiday_offset) {
        $holiday = strtotime("{$easter_sunday} {$holiday_offset} days");
        if ($collection_date == date('Y-m-d', $holiday)) {
          // this is one of the easter-releated holidays
          return FALSE;
        }
      }
    } else {
      CRM_Core_Session::setStatus(E::ts("Easter sunday not known for year %1. Please contact SYSTOPIA.", [1 => $year]), E::ts('Bank holiday list outdated'), 'warning');
    }

    // it all checks out, we can collect on this date
    return TRUE;
  }

  /**
   * Generate TX message, see https://projekte.systopia.de/redmine/issues/7254
   *
   * @param $mandate   array  mandate information
   * @param $creditor  array  creditor information
   */
  public static function generateTxMessage($mandate, $creditor) {
    if ($mandate['type'] == 'RCUR') {
      // load recurring contribution
      $rcontribution = civicrm_api3('ContributionRecur', 'getsingle', array(
          'id'     => $mandate['entity_id'],
          'return' => 'financial_type_id,frequency_interval,frequency_unit'));
      $financial_type = self::getFinancialTypeLabel($rcontribution['financial_type_id']);
      $payment_frequency = CRM_Utils_SepaOptionGroupTools::getFrequencyText($rcontribution['frequency_interval'], $rcontribution['frequency_unit'], true);
      $payment_frequency = preg_replace('/ä/', 'ae', $payment_frequency); // replace Umlaut in 'jährlich'
      return "{$financial_type} {$payment_frequency}. ProVeg sagt vielen Dank.";

    } else {
      // load contribution
      $contribution = civicrm_api3('Contribution', 'getsingle', array(
          'id'     => $mandate['contribution_id'],
          'return' => 'financial_type_id'));
      $financial_type = self::getFinancialTypeLabel($contribution['financial_type_id']);
      return "{$financial_type} - ProVeg sagt vielen Dank.";
    }
    return "ProVeg sagt vielen Dank.";
  }

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

  /**
   * Look up a financial type name
   *
   * @param $financial_type_id
   */
  protected static function getFinancialTypeLabel($financial_type_id) {
    if (self::$FINANCIAL_TYPE_NAMES === NULL) {
      $ft_query = civicrm_api3('FinancialType', 'get', array(
          'option.limit' => 0,
          'return'       => 'id,name',
          'sequential'   => 0));
      self::$FINANCIAL_TYPE_NAMES = $ft_query['values'];
    }
    if (isset(self::$FINANCIAL_TYPE_NAMES[$financial_type_id]['name'])) {
      return self::$FINANCIAL_TYPE_NAMES[$financial_type_id]['name'];
    } else {
      // this shouldn't happen
      return 'Unknown';
    }
  }

}