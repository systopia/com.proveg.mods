<?php
/*-------------------------------------------------------+
| SYSTOPIA ProVegMods Extension                          |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * Class CRM_Mods_SubscriptionLogger
 *
 */
class CRM_Mods_SubscriptionLogger {

  private $_log_file;

  // data
  private $contact_id;
  private $email;
  private $group_id_hashes;

  /**
   * CRM_Mods_SubscriptionLogger constructor.
   */
  public function __construct(&$form) {
    $config = CRM_Mods_Config::singleton();
    $file = CRM_Core_Config::singleton()->configAndLogDir . $config->get_log_file_name() .'.log';
    $this->_log_file = fopen($file, 'a');

    $this->email = $_POST['email-Primary'];
    $this->contact_id = $form->_id;
  }

  private function get_hash_value($email, $group_id, $contact_id) {

  }

  private function get_email_id($email) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'email' => $email,
    ]);
    $highest_id = 0;
    foreach ($result['values'] as $value) {
      $email_id = $value['id'];
      if ($email_id > $highest_id) {
        $highest_id = $email_id;
      }
    }
    return $highest_id;
  }

  /**
   * @param $form
   */
  public function log_subscription() {
    // get contact_id


    $group_ids = [];
    $hash_values = [];
  }

  public function set_bulkflag() {

  }


  /**
   * Log to File
   *
   * @param $type
   * @param $message
   */
  private function log_to_file($type, $message) {
    fputs($this->_log_file, date('Y-m-d h:i:s'));
    fputs($this->_log_file, ' ');
    fputs($this->_log_file, $type);
    fputs($this->_log_file, ' ');
    fputs($this->_log_file, $message);
    fputs($this->_log_file, "\n");
  }

}