<?php
/*-------------------------------------------------------+
| ProVegMods Custom API                                  |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * Specifications for ProVegMods.membership_postprocess:
 *   Postprocessing for a newly created membership
 */
function _civicrm_api3_pro_veg_mods_membership_postprocess_spec(&$params) {
  $params['membership_id'] = array(
      'name'         => 'membership_id',
      'title'        => 'Membership ID',
      'description'  => 'ID of the membership to be updated',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_INT
  );
  $params['contact_id'] = array(
      'name'         => 'contact_id',
      'title'        => 'Contact ID',
      'description'  => 'ID of the member\'s contact',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_INT
  );
  $params['organization_id'] = array(
      'name'         => 'organization_id',
      'title'        => 'Organisation ID',
      'description'  => 'ID of the member\'s organisation (if any)',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_INT
  );
  $params['contribution_id'] = array(
      'name'         => 'contribution_id',
      'title'        => 'Contribution ID',
      'description'  => 'ID membership payment (if any)',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_INT
  );
  $params['recurring_contribution_id'] = array(
      'name'         => 'recurring_contribution_id',
      'title'        => 'Recurring Contribution ID',
      'description'  => 'ID membership recurring payment (if any)',
      'api.required' => 0,
      'type'         => CRM_Utils_Type::T_INT
  );
}

/**
 * API Call ProVegMods.membership_postprocess:
 *   Postprocessing for a newly created membership
 */
function civicrm_api3_pro_veg_mods_membership_postprocess($params) {
  CRM_Mods_Memberships::newMembershipPostprocess(
      $params['membership_id'],
      $params['contact_id'],
      $params['recurring_contribution_id'],
      FALSE
  );
  return civicrm_api3_create_success();
}

