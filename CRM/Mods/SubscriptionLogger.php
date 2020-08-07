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
  private $hash;
  private $group_id;

  private $logging_types = ["ProVegApi", "MailingEventConfirm"];


  /**
   * CRM_Mods_SubscriptionLogger constructor.
   *
   * @param $contact_id
   * @param $hash
   * @param $group_id
   * @param null $email
   */
  public function __construct($contact_id, $hash, $group_id, $email = NULL) {
    $config = CRM_Mods_Config::singleton();
    $file = CRM_Core_Config::singleton()->configAndLogDir . $config->get_log_file_name() .'.log';
    $this->_log_file = fopen($file, 'a');

    $this->contact_id = $contact_id;
    $this->group_id = $group_id;
    $this->hash = $hash;
    if (!empty($email)) {
      $this->email = $email;
    }
  }


  /**
   * @param $type
   * type is either via API, or via Wrapper from MailingEventConfirm
   */
  public function log_subscription($type) {
    if (!in_array($type, $this->logging_types)) {
      CRM_Core_Error::debug_log_message("[CRM_Mods_SubscriptionLogger] Invalid logging Type '{$type}'. Must be in " . json_encode($this->logging_types));
      return;
    }
    $message = "[{$this->contact_id}] >> Group_id: {$this->group_id}, Hash: {$this->hash}";
    if (isset($this->email)) {
      $message .= ", {$this->email}";
    }
    $this->log_to_file($type, $message);
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
    fputs($this->_log_file, "Action: " . $type);
    fputs($this->_log_file, ' ');
    fputs($this->_log_file, $message);
    fputs($this->_log_file, "\n");
  }

}