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

/**
 * Implements CiviSCRM hook to inject JS
 */
function mods_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Sepa_Page_CreateMandate') {
    CRM_Core_Resources::singleton()->addScriptFile('com.proveg.mods', 'js/PreselectRCUR.js');
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mods_civicrm_config(&$config) {
  _mods_civix_civicrm_config($config);
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
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _mods_civix_insert_navigation_menu($menu, 'my_contact', array(
      'label' => E::ts('My Contact in CiviCRM'),
      'name' => 'my_contact_civicrm',
      'url' => 'civicrm/contact/view',
      'icon' => 'Individual-icon icon crm-icon',
      'permission' => 'access CiviCRM',
      'operator' => 'OR',
      'separator' => 0,
  ));
  _mods_civix_insert_navigation_menu($menu, 'my_contact', array(
      'label' => E::ts('My Contact in Drupal'),
      'name' => 'my_contact_civicrm',
      'url' => 'user',
      'icon' => 'Individual-icon icon crm-icon',
      'permission' => 'access CiviCRM',
      'operator' => 'OR',
      'separator' => 0,
  ));
  _mods_civix_navigationMenu($menu);
}
