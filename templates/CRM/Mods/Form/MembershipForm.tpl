{*-------------------------------------------------------+
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
+--------------------------------------------------------*}

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}


<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script>
  function mods_update_gender() {
    // predefined mapping
    // FIXME: setting?
    switch (cj('#prefix_id').val()) {
      case '1': // female
      case '2':
      case '5':
        cj('#gender_id').val('1');
        break;

      case '6': // male
      case '3':
        cj('#gender_id').val('2');
        break;

      case '': // empty
        cj('#gender_id').val('3');
        break;

      default:
        // nothing to do

    }
  }

  // connect and run
  cj(document).ready(function() {
    cj('#prefix_id').change(mods_update_gender);
  });
  mods_update_gender();
</script>
{/literal}