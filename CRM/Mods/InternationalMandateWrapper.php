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
 * Will make sure that all mandates from
 *   Schweiz, Island, Liechtenstein, Norwegen, Großbritannien and Monaco
 *   ('CH', 'IS', 'NO', 'GB', 'MC')
 * will be assigned to creditor 2 (intl)
 */
class CRM_Mods_InternationalMandateWrapper implements API_Wrapper {

  /**
   * Set creditor_id = 2 if IBAN is 'CH', 'IS', 'NO', 'GB', or 'MC'
   *
   * @param array $apiRequest
   *
   * @return array
   *   modified $apiRequest
   *
   * @throws CiviCRM_API3_Exception
   */
  public function fromApiInput($apiRequest) {
    if ($apiRequest['entity'] == 'SepaMandate'
        && ($apiRequest['action'] == 'create' || $apiRequest['action'] == 'createfull')) {
      // this is a sepa mandate being created / edited
      $iban = NULL;
      if (!empty($apiRequest['params']['iban'])) {
        // iban is submitted
        $iban = $apiRequest['params']['iban'];
      } elseif (!empty($apiRequest['params']['id'])) {
        // ID is submitted
        $iban = civicrm_api3('SepaMandate', 'getvalue', [
            'id'     => $apiRequest['params']['id'],
            'return' => 'iban']);
      }

      // no if we have an iban, do the check
      if ($iban) {
        $country = strtoupper(substr($iban, 0, 2));
        if (in_array($country, ['CH', 'IS', 'NO', 'GB', 'MC'])) {
          // country is Schweiz, Island, Liechtenstein, Norwegen, Großbritannien or Monaco:
          // set creditor 2 (intl)
          $apiRequest['params']['creditor_id'] = 2;
        }
      }
    }
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