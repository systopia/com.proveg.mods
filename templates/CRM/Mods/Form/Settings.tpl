{*-------------------------------------------------------+
| SYSTOPIA MailingTools Extension                        |
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
+-------------------------------------------------------*}

<br/><h3>{ts domain='com.proveg.mods'}Proveg Custom Logging Configuration{/ts}</h3><br/>

<div class="crm-section">
  <div class="label">{$form.activate_subscription_logging.label} <a onclick='CRM.help("{ts domain='com.proveg.mods'}Activate Subscription Logging{/ts}", {literal}{"id":"id-mod-logging-activation","file":"CRM\/Mods\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain='com.proveg.mods'}Help{/ts}" class="helpicon">&nbsp;</a></div>
  <div class="content">{$form.activate_subscription_logging.html}</div>
  <div class="clear"></div>
</div>



<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>