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
 * Custom Membership Form:
 *  - create contact
 *  - create membership
 *  - crete and link SEPA mandate
 */
class CRM_Mods_Form_MembershipForm extends CRM_Core_Form {
  const MEMBERSHIP_FORM_SOURCE = 'Paper Form';

  public function buildQuickForm() {

    // add contact elements
    $this->add(
        'select',
        'prefix_id',
        E::ts('Individual Prefix'),
        $this->getPrefixes(),
        FALSE
    );
    $this->add(
      'text',
      'first_name',
      E::ts('First Name'),
      [],
      TRUE
    );
    $this->add(
        'text',
        'last_name',
        E::ts('Last Name'),
        [],
        TRUE
    );
    $this->add(
        'select',
        'gender_id',
        E::ts('Gender'),
        $this->getGenders(),
        FALSE
    );
    $this->add(
        'datepicker',
        'birth_date',
        E::ts('Birth Date'),
        [],
        FALSE,
        ['time' => FALSE]
    );
    $this->add(
      'select',
      'preferred_language',
      E::ts('Preferred Language'),
      $this->getPreferredLanguages(),
      TRUE
    );

    // add address fields
    $this->add(
        'text',
        'street_address',
        E::ts('Street Address'),
        ['class' => 'huge'],
        TRUE
    );
    $this->add(
        'text',
        'supplemental_address_1',
        E::ts('Supplemental Address'),
        ['class' => 'huge'],
        FALSE
    );
    $this->add(
        'text',
        'postal_code',
        E::ts('Postal Code'),
        ['size' => '5'],
        TRUE
    );
    $this->add(
        'text',
        'city',
        E::ts('City'),
        [],
        TRUE
    );
    $this->add(
        'select',
        'country_id',
        E::ts('Country'),
        $this->getCountries(),
        TRUE
    );

    // add details fields
    $this->add(
        'text',
        'email',
        E::ts('Email'),
        [],
        FALSE
    );
    $this->add(
        'text',
        'phone',
        E::ts('Phone'),
        [],
        FALSE
    );

    // add membership fields
    $this->add(
        'datepicker',
        'join_date',
        E::ts('Member Since'),
        [],
        TRUE,
        ['time' => FALSE]
    );
    $this->add(
        'select',
        'membership_type_id',
        E::ts('Membership Type'),
        $this->getMembershipTypes(),
        TRUE
    );
    $this->add(
        'select',
        'fee_type',
        E::ts('Fee Type'),
        $this->getFeeTypeOptions(),
        FALSE
    );
    $this->add(
        'select',
        'campaign_id',
        E::ts('Campaign'),
        $this->getCampaigns(),
        TRUE
    );
    $this->add(
        'file',
        'contract_file',
        E::ts('Contract Scan'),
        TRUE
    );

    // add payment fields
    $this->add(
        'text',
        'amount',
        E::ts('Amount'),
        [],
        TRUE
    );
    $this->add(
        'select',
        'frequency_interval',
        E::ts('Frequency'),
        [1 => E::ts("monthly"), 3 => E::ts("quarterly"), 6 => E::ts("semi-annually"), 12 => E::ts("annually")],
        TRUE
    );
    $this->add(
        'text',
        'iban',
        E::ts('IBAN'),
        ['class' => 'huge'],
        TRUE
    );
    $this->add(
        'text',
        'bic',
        E::ts('BIC'),
        [],
        FALSE
    );


    // set last-used defaults:
    $defaults = Civi::settings()->get('proveg_membership_paperform_defaults');
    if (is_array($defaults)) {
      $this->setDefaults($defaults);
    }

    // set prefix default 'Frau'
    $this->setDefaults(
      [
          'prefix_id'          => 5,
          'preferred_language' => 'de_DE'
      ]
    );

      // add button
    $this->addButtons([
        [
            'type'      => 'submit',
            'name'      => E::ts('Create'),
            'isDefault' => TRUE,
        ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }


  public function validate() {
    // validate IBAN
    $iban_error = CRM_Sepa_Logic_Verification::verifyIBAN($this->_submitValues['iban']);
    if ($iban_error) {
      $this->_errors['iban'] = $iban_error;
    } else {
      // all good -> look up bic
      if (empty($this->_submitValues['bic'])) {
        $lookup = civicrm_api3('Bic', 'findbyiban', ['iban' => $this->_submitValues['iban']]);
        if (empty($lookup['bic'])) {
          $this->_errors['bic'] = E::ts("BIC is required");
        } else {
          $this->_submitValues['bic'] = $lookup['bic'];
        }
      }
    }

    // validate amount
    $amount = (float) $this->_submitValues['amount'];
    if (!$amount) {
      $this->_errors['amount'] = E::ts("Please enter a valid amount");
    }

    parent::validate();
    return (0 == count($this->_errors));
  }

  public function postProcess() {
    $values = $this->exportValues();

    // store new defaults
    Civi::settings()->set('proveg_membership_paperform_defaults', [
        'join_date'          => $values['join_date'],
        'campaign_id'        => $values['campaign_id'],
        'membership_type_id' => $values['membership_type_id'],
        'fee_type'           => $values['fee_type'],
        'country_id'         => $values['country_id'],
    ]);

    // create contact
    $contact_data = [
        'contact_type' => 'Individual'
    ];
    foreach (['prefix_id', 'first_name', 'last_name', 'birth_date', 'email', 'gender_id', 'preferred_language'] as $attribute) {
      $contact_data[$attribute] = $values[$attribute];
    }
    // call api
    $contact = civicrm_api3('Contact', 'create', $contact_data);

    // add phone
    if (!empty($values['phone'])) {
      $phone_data = [
          'location_type_id' => 1,
          'contact_id'       => $contact['id'],
          'phone_type_id'    => 1,
          'phone'            => $values['phone']
      ];
      $phone = civicrm_api3('Phone', 'create', $phone_data);
    }


    // create address
    $address_data = [
        'location_type_id' => 1,
        'contact_id'       => $contact['id'],
    ];
    foreach (['street_address', 'supplemental_address_1', 'postal_code', 'city', 'country_id'] as $attribute) {
      $address_data[$attribute] = $values[$attribute];
    }
    $address = civicrm_api3('Address', 'create', $address_data);

    // add contract file
    if (empty($_FILES['contract_file']['name']) || empty($_FILES['contract_file']['tmp_name'])) {
        CRM_Core_Session::setStatus(E::ts("No contract file submitted!"), E::ts("Contract Scan Missing"), 'warning');
    } else {
      try {
        // create contract activity with attachment
        $activity = civicrm_api3('Activity', 'create', [
            'activity_type_id'        => 'Contract',
            'subject'                 => "Mitgliedsvertrag",
            'activity_date_time'      => date('YmdHis'),
            'target_id'               => $contact['id'],
            'status_id'               => 'Completed',
            'source_contact_id'       =>  CRM_Core_Session::getLoggedInContactID(),
        ]);
        $this->attachFile($_FILES['contract_file'], $activity['id']);
      } catch (Exception $ex) {
        CRM_Core_Session::setStatus(E::ts("Couldn't create contract activity: %1", [1 => $ex->getMessage()]), E::ts("Activities Missing"), 'error');
      }
    }

    // create contact source activity
    try {
      if (!empty($values['campaign_id'])) {
        civicrm_api3('Activity', 'create', [
            'activity_type_id'   => 'contact_source',
            'subject'            => civicrm_api3('Campaign', 'getvalue', ['id' => $values['campaign_id'], 'return' => 'title']),
            'activity_date_time' => date('YmdHis'),
            'target_id'          => $contact['id'],
            'campaign_id'        => $values['campaign_id'],
            'status_id'          => 'Completed',
            'source_contact_id'  => CRM_Core_Session::getLoggedInContactID(),
        ]);
      }
    } catch (Exception $ex) {
      CRM_Core_Session::setStatus(E::ts("Couldn't create source activity: %1", [1 => $ex->getMessage()]), E::ts("Activities Missing"), 'error');
    }

    // create membership
    $start_date = CRM_Mods_Memberships::calculateStartDate($values['join_date']);
    $membership_data = [
        'contact_id' => $contact['id'],
        'start_date' => $start_date,
        'source'     => self::MEMBERSHIP_FORM_SOURCE,
    ];
    foreach (['join_date', 'membership_type_id', 'campaign_id'] as $attribute) {
      $membership_data[$attribute] = $values[$attribute];
    }
    $membership = civicrm_api3('Membership', 'create', $membership_data);

    // set the fee type
    civicrm_api3('Membership', 'create', [
      'id' => $membership['id'],
      CRM_Mods_Memberships::FEE_TYPE_FIELD  => $values['fee_type'],
    ]);

    // create sepa mandate
    $mandate_data = [
        'contact_id'        => $contact['id'],
        'start_date'        => $start_date,
        'type'              => 'RCUR',
        'frequency_unit'    => 'month',
        'financial_type_id' => 2,
        'source'            => self::MEMBERSHIP_FORM_SOURCE,
    ];
    foreach (['iban', 'bic', 'frequency_interval', 'amount'] as $attribute) {
      $mandate_data[$attribute] = $values[$attribute];
    }
    $mandate = civicrm_api3('SepaMandate', 'createfull', $mandate_data);
    $mandate = civicrm_api3('SepaMandate', 'getsingle', ['id' => $mandate['id']]);

    // run general post processing
    CRM_Mods_Memberships::newMembershipPostprocess($membership['id'], $contact['id'], $mandate['entity_id'], TRUE);

    // inform user
    CRM_Core_Session::setStatus(E::ts('Contact, mandate and membership created (<a href="%2">Contact [%1]</a>).', [
        1 => $contact['id'],
        2 => CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contact['id']}")]
    ), E::ts("Success"), 'info');

    // move on...
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/member/paperform', 'reset=1'));
    parent::postProcess();
  }

  /**
   * Attach the given file to the activity
   *
   * @param $upload       array upload metadata
   * @param $activity_id  integer activity ID
   */
  public function attachFile($upload, $activity_id) {
    // find a place to put the file
    $config = CRM_Core_Config::singleton();
    $persistent_file_name = CRM_Utils_File::makeFileName($upload['name']);
    $persistent_file_path = $config->uploadDir . DIRECTORY_SEPARATOR . $persistent_file_name;

    // move it there
    copy($upload['tmp_name'], $persistent_file_path);
    unlink($upload['tmp_name']);

    // attach to the activity
    $attachment = ['attachFile_1' => [
        'uri'         => $persistent_file_path,
        'location'    => $persistent_file_path,
        'upload_date' => date('YmdHis'),
        'type'        => $upload['type'],
    ]];
    CRM_Core_BAO_File::processAttachment($attachment, 'civicrm_activity', $activity_id);
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Get individual prefix options
   */
  protected function getPrefixes() {
    $options = ['' => E::ts('no Prefix')];
    $query = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'individual_prefix',
        'option.limit'    => 0,
        'is_active'       => 1,
        'return'          => 'value,label'
    ]);
    foreach ($query['values'] as $option) {
      $options[$option['value']] = $option['label'];
    }
    return $options;
  }

    /**
     * Get individual prefix options
     */
    protected function getFeeTypeOptions() {
        $options = ['' => E::ts('keine Beitragsart')];
        $query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'fee_type',
            'option.limit'    => 0,
            'is_active'       => 1,
            'return'          => 'value,label'
        ]);
        foreach ($query['values'] as $option) {
            $options[$option['value']] = $option['label'];
        }
        // remove type 'Abonnement', see https://projekte.systopia.de/issues/12016#note-7
        unset($options['4']);

        return $options;
    }

  /**
   * Get individual prefix options
   */
  protected function getGenders() {
    $options = ['' => E::ts('-select-')];
    $query = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'gender',
        'option.limit'    => 0,
        'is_active'       => 1,
        'return'          => 'value,label'
    ]);
    foreach ($query['values'] as $option) {
      $options[$option['value']] = $option['label'];
    }
    return $options;
  }

    /**
     * Get individual prefix options
     */
    protected function getPreferredLanguages() {
        $options = [];
        $query = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'languages',
            'option.limit'    => 0,
            'is_active'       => 1,
            'return'          => 'name,label'
        ]);
        foreach ($query['values'] as $option) {
            $options[$option['name']] = $option['label'];
        }
        return $options;
    }

  /**
   * Get individual prefix options
   */
  protected function getCountries() {
    $options = [];
    $query = civicrm_api3('Country', 'get', [
        'option.limit'    => 0,
        'return'          => 'id,name'
    ]);
    foreach ($query['values'] as $option) {
      $options[$option['id']] = $option['name'];
    }
    return $options;
  }

  /**
   * Get individual prefix options
   */
  protected function getMembershipTypes() {
    $options = [];
    $query = civicrm_api3('MembershipType', 'get', [
        'option.limit'    => 0,
        'return'          => 'id,name'
    ]);
    foreach ($query['values'] as $option) {
      $options[$option['id']] = $option['name'];
    }
    return $options;
  }

  /**
   * Get individual prefix options
   */
  protected function getCampaigns() {
    $options = [];
    $query = civicrm_api3('Campaign', 'get', [
        'option.limit' => 0,
        'is_active'    => 1,
        'return'       => 'id,title'
    ]);
    foreach ($query['values'] as $option) {
      $options[$option['id']] = $option['title'];
    }
    return $options;
  }
}
