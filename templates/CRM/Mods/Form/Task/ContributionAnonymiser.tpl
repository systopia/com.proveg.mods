{*-------------------------------------------------------+
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
+--------------------------------------------------------*}

{crmScope extensionKey='com.proveg.mods'}
    <div class="crm-section">
        <div class="label">{ts}Contribution count:{/ts} </div>
        <div class="content">{$contributionCount}</div>
        <div class="clear"></div>
    </div>

    <div class="crm-section">
        <div class="label">{ts}Recurring contribution count:{/ts} </div>
        <div class="content">{$recurringContributionCount}</div>
        <div class="clear"></div>
    </div>

    <div class="crm-section">
        <div class="label">{$form.anonymous_contact.label}</div>
        <div class="content">{$form.anonymous_contact.html}</div>
        <div class="clear"></div>
    </div>

    {* FOOTER *}
    <br>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
{/crmScope}
