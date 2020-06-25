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

require_once 'mods.civix.php';
use CRM_Mods_ExtensionUtil as E;

/**
 * Implements emailProcessor hook
 *
 * @see PV-8843
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_emailProcessorContact/
 */
function mods_civicrm_emailProcessorContact( $email, $contactID, &$result ) {
  CRM_Mods_Emailprocessor::lookupContact($email, $contactID, $result);
}

/**
 * Implements CiviSEPA hook
 */
function mods_civicrm_create_mandate(&$mandate_parameters) {
  if (empty($mandate_parameters['reference'])) {
    CRM_Mods_SepaMandate::createMandateReference($mandate_parameters);
  }
}

/**
 * Implements CiviSEPA hook to adjust collection date
 */
function mods_civicrm_defer_collection_date(&$collection_date, $creditor_id) {
  while (!CRM_Mods_SepaMandate::is_collection_day($collection_date)) {
    $collection_date = date('Y-m-d', strtotime("+1 day", strtotime($collection_date)));
  }
}

/**
 * Implements CiviSEPA hook to adjust transaction message ("Verwendungszweck")
 */
function mods_civicrm_modify_txmessage(&$txmessage, $info, $creditor) {
  $txmessage = CRM_Mods_SepaMandate::generateTxMessage($info, $creditor);
}

function mods_civicrm_searchTasks($objectType, &$tasks) {
  // add "Anonymise contributions" task to contact list:
  if ($objectType == 'contact') {
    $tasks[] = [
      'title' => E::ts('Anonymise contributions'),
      'class' => 'CRM_Mods_Form_Task_ContributionAnonymiser',
      'result' => false
    ];
  }
}

///**
// * Implements CiviSCRM hook to inject JS
// */
//function mods_civicrm_pageRun(&$page) {
//  $pageName = $page->getVar('_name');
//}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mods_civicrm_config(&$config) {
  _mods_civix_civicrm_config($config);

  require_once 'CRM/Xdedupe/ProVeg.php';
  \Civi::dispatcher()->addSubscriber(new CRM_Xdedupe_ProVeg());
}

/**
 * Implements hook_validateForm
 *
 * @see PV-11651
 */
function mods_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if (($formName == 'CRM_Contact_Form_Contact') || $formName == 'CRM_Contact_Form_Inline_CommunicationPreferences') {
    if (($fields['preferred_language'] == NULL) || empty($fields['preferred_language'])) {
      $errors['preferred_language'] = E::ts('%1 is a required field.', [1 => 'preferred_language']);
    }
  }
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mods_civicrm_xmlMenu(&$files) {
  _mods_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mods_civicrm_install() {
  _mods_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function mods_civicrm_postInstall() {
  _mods_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mods_civicrm_uninstall() {
  _mods_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mods_civicrm_enable() {
  _mods_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mods_civicrm_disable() {
  _mods_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mods_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mods_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mods_civicrm_managed(&$entities) {
  _mods_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function mods_civicrm_caseTypes(&$caseTypes) {
  _mods_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function mods_civicrm_angularModules(&$angularModules) {
  _mods_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mods_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mods_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function mods_civicrm_entityTypes(&$entityTypes) {
  _mods_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Add ME Menu (PV-7673)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function mods_civicrm_navigationMenu(&$menu) {
  _mods_civix_insert_navigation_menu($menu, '', array(
    'label' => E::ts('Me'),
    'name' => 'my_contact',
    'url' => '',
    'icon' => 'fa fa-user-circle',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _mods_civix_insert_navigation_menu($menu, 'my_contact', array(
      'label' => E::ts('My Contact in CiviCRM'),
      'name' => 'my_contact_civicrm',
      'url' => 'civicrm/me',
      'permission' => 'access CiviCRM',
      'operator' => 'OR',
      'separator' => 0,
  ));
  _mods_civix_insert_navigation_menu($menu, 'my_contact', array(
      'label' => E::ts('My Contact in Drupal'),
      'name' => 'my_contact_drupal',
      'url' => 'user',
      'permission' => 'access CiviCRM',
      'operator' => 'OR',
      'separator' => 0,
  ));
  if (function_exists('statustracker_civicrm_config')) {
    _mods_civix_insert_navigation_menu($menu, 'my_contact', array(
        'label' => E::ts('My Processes'),
        'name' => 'my_processes',
        'url' => 'civicrm/statustracker/dashboard',
        'permission' => 'access CiviCRM',
        'operator' => 'OR',
        'separator' => 0,
    ));
  }

  // add paper form link
  _mods_civix_insert_navigation_menu($menu, 'Memberships', array(
      'label' => E::ts('Paper Form'),
      'name' => 'membership_paperform',
      'url' => 'civicrm/member/paperform',
      'permission' => 'edit memberships',
      'operator' => 'OR',
      'separator' => 0,
  ));

  _mods_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function mods_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Merge') {
    // see PV-7858
    CRM_Core_Resources::singleton()->addScriptFile('com.proveg.mods', 'js/merge_mods.js');
  }
}

/**
 * Implements mods_civicrm_gdprx_postConsent().
 *
 * @see https://github.com/systopia/de.systopia.gdprx/issues/9
 */
function mods_civicrm_gdprx_postConsent($mode, $contact_id, $record_id, $data) {
  CRM_Mods_Gdpr::updatePrivacySettings($contact_id);
}

/**
 * Implements hook_civicrm_apiWrappers
 */
function mods_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if ($apiRequest['entity'] == 'SepaMandate'
      && in_array($apiRequest['action'], ['create', 'createfull'])) {
    $wrappers[] = new CRM_Mods_InternationalMandateWrapper();
  }
}

/**
 * Implements hook_civicrm_pre
 */
function mods_civicrm_pre($op, $objectName, $id, &$params) {
  // Issue a warning if somebody edits a contact with an active membership
  if ((!empty($id) && $op == 'edit') &&
      ($objectName == 'Individual' || $objectName == 'Organization' || $objectName == 'Household')) {

    CRM_Mods_CardTitle::showCardTitleShouldBeAdjustedWarning($id, $params);
  }
}


/**
 * Implements hook_civicrm_post
 */
function mods_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'Membership') {

    // make sure we don't cause a recursion
    static $disable_card_title_update = false;
    if ($disable_card_title_update) return;

    // make sure the card title field is there
    $CUSTOM_FIELD_ID = CRM_Mods_CardTitle::getCardTitleFieldID();
    if (!$CUSTOM_FIELD_ID) return;
    $title_field = "custom_{$CUSTOM_FIELD_ID}";


    // check if it's empty and
    try {
      $membership = civicrm_api3('Membership', 'getsingle', [
          'id'     => $objectId,
          'return' => "{$title_field},id,contact_id"
      ]);

      // if field is empty -> calculate new value
      if (empty($membership[$title_field])) {
        $field_list = ['formal_title','first_name','last_name'];
        $contact = civicrm_api3('Contact', 'getsingle', [
            'id'     => $membership['contact_id'],
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

        // set new title
        $disable_card_title_update = true;
        civicrm_api3('Membership', 'create', [
            'id'         => $objectId,
            $title_field => trim(implode(' ', $pieces))
        ]);
      }
    } catch (Exception $ex) {
      // something went wrong
      CRM_Core_Error::debug_log_message("mods: Error while setting ProVeg Card Title: " . $ex->getMessage());
    }
  }
}
