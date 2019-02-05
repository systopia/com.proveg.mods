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
class CRM_Mods_Gdpr {

  protected static $fields = [
      'postal' => 'do_not_mail',
      'email'  => 'do_not_email',
      'phone'  => 'do_not_phone',
      'newsl'  => 'is_opt_out'];

  /**
   * Recalculate and update the privacy settings of the given contact
   *
   * @param $contact_id int contact ID
   * @throws Exception if DB query goes wrong - shouldn't happen
   */
  public static function updatePrivacySettings($contact_id) {
    $contact_id = (int) $contact_id;

    $last_opt_in = CRM_Core_DAO::executeQuery("
      SELECT 
        MAX(postal.date) AS postal,
        MAX(email.date)  AS email,
        MAX(phone.date)  AS phone,
        MAX(newsl.date)  AS newsl
      FROM civicrm_contact contact
      LEFT JOIN civicrm_value_gdpr_consent postal ON postal.entity_id = contact.id AND postal.category = 22 AND postal.type IN (2,4,5)
      LEFT JOIN civicrm_value_gdpr_consent email  ON email.entity_id  = contact.id AND email.category  = 20 AND email.type  IN (2,4,5)
      LEFT JOIN civicrm_value_gdpr_consent phone  ON phone.entity_id  = contact.id AND phone.category  = 21 AND phone.type  IN (2,4,5)
      LEFT JOIN civicrm_value_gdpr_consent newsl  ON newsl.entity_id  = contact.id AND newsl.category  = 23 AND newsl.type  IN (2,4,5)
      WHERE contact.id = {$contact_id}");
    $last_opt_in->fetch();

    $last_opt_out = CRM_Core_DAO::executeQuery("
      SELECT
        MAX(postal.date) AS postal,
        MAX(email.date)  AS email,
        MAX(phone.date)  AS phone,
        MAX(newsl.date)  AS newsl
      FROM civicrm_contact contact
      LEFT JOIN civicrm_value_gdpr_consent postal ON postal.entity_id = contact.id AND postal.category = 22 AND postal.type IN (3,6)
      LEFT JOIN civicrm_value_gdpr_consent email  ON email.entity_id  = contact.id AND email.category  = 20 AND email.type  IN (3,6)
      LEFT JOIN civicrm_value_gdpr_consent phone  ON phone.entity_id  = contact.id AND phone.category  = 21 AND phone.type  IN (3,6)
      LEFT JOIN civicrm_value_gdpr_consent newsl  ON newsl.entity_id  = contact.id AND newsl.category  = 23 AND newsl.type  IN (3,6)
      WHERE contact.id = {$contact_id}");
    $last_opt_out->fetch();

    CRM_Core_Error::debug_log_message(json_encode($last_opt_in));
    CRM_Core_Error::debug_log_message(json_encode($last_opt_out));

    $contact_update = ['id' => $contact_id];
    foreach (self::$fields as $query_field => $contact_field) {
      if (  empty($last_opt_in->$query_field)     /* no opt-in */
         || (!empty($last_opt_out->$query_field)  /* OR opt-out after opt-in */
               && $last_opt_in->$query_field < $last_opt_out->$query_field)) {
        $contact_update[$contact_field] = 1;
      } else {
        $contact_update[$contact_field] = 0;
      }
    }

    // run the update
    CRM_Core_Error::debug_log_message("Contact.update: " . json_encode($contact_update));
    civicrm_api3('Contact', 'create', $contact_update);
  }
}