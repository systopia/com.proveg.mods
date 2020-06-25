<?php

/*-------------------------------------------------------+
| proVeg Germany Adjustments                             |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * Anonymise contributions by moving them from their associated contacts to an anonymous contact.
 */
class CRM_Mods_Form_Task_ContributionAnonymiser extends CRM_Contact_Form_Task
{
    private const CONTRIBUTION_ANONYMISER_SETTINGS_KEY = 'proveg_mods_contribution_anonymiser_settings';

    private const ANONYMOUS_CONTACT_ID_SETTINGS_KEY = 'anonymous_contact_id';

    private const ANONYMOUS_CONTACT_ELEMENT_NAME = 'anonymous_contact';

    public function buildQuickForm()
    {
        parent::buildQuickForm();

        $contributionCount = $this->getEntityCountForContactIds('Contribution', $this->_contactIds);
        $recurringContributionCount = $this->getEntityCountForContactIds('ContributionRecur', $this->_contactIds);

        $this->assign('contributionCount', $contributionCount);
        $this->assign('recurringContributionCount', $recurringContributionCount);

        $this->addEntityRef(
            self::ANONYMOUS_CONTACT_ELEMENT_NAME,
            E::ts('Anonymous contact'),
            [
                'api' => [
                    'params' => []
                ]
            ],
            true
        );

        $defaults = [];

        $settings = Civi::settings()->get(self::CONTRIBUTION_ANONYMISER_SETTINGS_KEY);

        // Prefill the anonymous contact ID if there is one in the settings:
        if ($settings && is_array($settings) && array_key_exists(self::ANONYMOUS_CONTACT_ID_SETTINGS_KEY, $settings)) {
            $defaults[self::ANONYMOUS_CONTACT_ELEMENT_NAME] = $settings[self::ANONYMOUS_CONTACT_ID_SETTINGS_KEY];
        }

        $this->setDefaults($defaults);
    }

    public function postProcess()
    {
        parent::postProcess();

        $values = $this->exportValues(null, true);
        $anonymousContactId = $values[self::ANONYMOUS_CONTACT_ELEMENT_NAME];

        // Save the anonymous contact ID in the settings to prefill it the next time:
        $settings = [
            self::ANONYMOUS_CONTACT_ID_SETTINGS_KEY => $anonymousContactId,
        ];
        Civi::settings()->set(self::CONTRIBUTION_ANONYMISER_SETTINGS_KEY, $settings);

        $contributionIds = $this->getEntityIdsForContactIds('Contribution', $this->_contactIds);

        $transaction = new CRM_Core_Transaction();

        try {
            $this->anonymiseContribution($this->_contactIds, $anonymousContactId);
            $this->anonymiseRecurringContribution($this->_contactIds, $anonymousContactId);
            $this->anonymiseFinancialItem($this->_contactIds, $anonymousContactId);
            $this->cleanBookingInfo($contributionIds);
        } catch (Exception $exception) {
            $transaction->rollback();

            throw $exception;
        }

        $transaction->commit();
    }

    /**
     * Transfer the contribution the the given anonymous contact.
     * @param string[] $contactIds
     * @param int $anonymousContactId
     */
    public function anonymiseContribution(array $contactIds, int $anonymousContactId): void
    {
        foreach ($contactIds as $contactId) {
            CRM_Core_DAO::executeQuery(
                'UPDATE
                    civicrm_contribution AS contribution
                SET
                    contribution.contact_id := %1
                WHERE
                    contribution.contact_id = %0
                ',
                [
                    0 => [$contactId, 'Int'],
                    1 => [$anonymousContactId, 'Int']
                ]
            );
        }
    }

    /**
     * Transfer the recurring contribution the the given anonymous contact.
     * @param string[] $contactIds
     * @param int $anonymousContactId
     */
    public function anonymiseRecurringContribution(array $contactIds, int $anonymousContactId): void
    {
        foreach ($contactIds as $contactId) {
            CRM_Core_DAO::executeQuery(
                'UPDATE
                    civicrm_contribution_recur AS contribution
                SET
                    contribution.contact_id := %1
                WHERE
                    contribution.contact_id = %0
                ',
                [
                    0 => [$contactId, 'Int'],
                    1 => [$anonymousContactId, 'Int']
                ]
            );
        }
    }

    /**
     * Transfer the financial item the the given anonymous contact.
     * @param string[] $contactIds
     * @param int $anonymousContactId
     */
    public function anonymiseFinancialItem(array $contactIds, int $anonymousContactId): void
    {
        foreach ($contactIds as $contactId) {
            CRM_Core_DAO::executeQuery(
                'UPDATE
                    civicrm_financial_item AS financial_item
                SET
                    financial_item.contact_id := %1
                WHERE
                    financial_item.contact_id = %0
                ',
                [
                    0 => [$contactId, 'Int'],
                    1 => [$anonymousContactId, 'Int']
                ]
            );
        }
    }

    /**
     * Will clean the booking info for the given contributions by setting sensitive fields to null.
     * @param string[] $contributionIds
     */
    public function cleanBookingInfo(array $contributionIds): void
    {
        foreach ($contributionIds as $contributionId) {
            CRM_Core_DAO::executeQuery(
                'UPDATE
                    civicrm_value_booking_info AS booking_info
                SET
                    booking_info.transaction_message := NULL,
                    booking_info.mandate_reference := NULL
                WHERE
                    booking_info.entity_id = %0
                ',
                [
                    0 => [$contributionId, 'Int']
                ]
            );
        }
    }

    /**
     * Get the count of entities from the API where the "contact_id" value is in a list of contact IDs.
     * @param string $entity
     * @param string[] $contactIds
     */
    private function getEntityCountForContactIds(string $entity, array $contactIds): int
    {
        /** @var int $apiResult */
        $apiResult = civicrm_api3(
            $entity,
            'getcount',
            [
                'contact_id' => [
                    'IN' => $contactIds
                ],
            ]
        );

        return $apiResult;
    }

    /**
     * Get a list of Ids from the API for an entity where the "contact_id" value is in a list of contact IDs.
     * @param string $entity
     * @param string[] $contactIds
     */
    private function getEntityIdsForContactIds(string $entity, array $contactIds): array
    {
        $apiResult = civicrm_api3(
            $entity,
            'get',
            [
                'contact_id' => [
                    'IN' => $contactIds
                ],
            ]
        );

        $result = [];
        foreach ($apiResult['values'] as $value) {
            $result[] = $value['id'];
        }

        return $result;
    }
}
