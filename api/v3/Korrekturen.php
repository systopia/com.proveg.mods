<?php
/*-------------------------------------------------------+
| SYSTOPIA PV Migration corrections                      |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * End badly imported mandates
 * @see https://projekte.systopia.de/redmine/issues/7245
 */
function civicrm_api3_korrekturen_pv7245($params) {
  $messages = [];
  $cancel_reason = "Migrationsfehler: nicht mehr aktiv.";
  $end_date = date('Y-m-d');
  $references = array('100932-01','118832-01','112638-01','114042-01','113314-01','114085-01','114088-01','114958-01','114589-01','115175-01','115379-01','115737-02','115805-01','115935-01','116241-01','116443-01','128086-01','116800-02','116894-01','125213-02','123624-01','117943-01','126656-01','118623-01','118686-01','118899-01','119122-01','119302-01','119650-01','120072-01','120315-01','122388-01','120681-01','120897-01','121247-01','122409-01','122521-01','123384-01','123616-01','124001-02','124436-01','124529-01','125184-01','125411-01','125601-01','125698-02','126207-02','126529-01','126507-01','127169-01','127520-02','127785-02','127812-01','127827-01','128490-01','129369-01','129915-01','131210-01','131952-01','132615-01');
  foreach ($references as $reference) {
    $mandate = civicrm_api3('SepaMandate', 'get', ['reference' => $reference, 'status' => 'RCUR']);
    if (!empty($mandate['id'])) {
      CRM_Sepa_BAO_SEPAMandate::terminateMandate($mandate['id'], $end_date, $cancel_reason);
      $messages[] = "Terminated: {$reference}";
    } else {
      $messages[] = "Not found: {$reference}";
    }
  }

  return civicrm_api3_create_success($messages);
}
