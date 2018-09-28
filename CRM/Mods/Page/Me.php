<?php
use CRM_Mods_ExtensionUtil as E;

/**
 * This is just a redirect to my contact
 */
class CRM_Mods_Page_Me extends CRM_Core_Page {

  public function run() {
    $contact_id = CRM_Core_Session::getLoggedInContactID();
    $link_to_my_contact = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contact_id}");
    CRM_Utils_System::redirect($link_to_my_contact);
    parent::run();
  }
}
