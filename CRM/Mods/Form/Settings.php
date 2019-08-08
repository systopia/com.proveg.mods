<?php

use CRM_Mods_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Mods_Form_Settings extends CRM_Core_Form {
  public function buildQuickForm() {

    $config = CRM_Mods_Config::singleton();
    $current_values = $config->getSettings();

    // add form elements
    // Token Tools
    $this->add(
      'checkbox',
      'activate_subscription_logging',
      E::ts('Activate Custom Subscription Logging')
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // set default values
    $this->setDefaults($current_values);

    parent::buildQuickForm();
  }

  public function postProcess() {
    $config = CRM_Mods_Config::singleton();
    $values = $this->exportValues();
    $config->setSettings($values);
    parent::postProcess();
  }

}
