{* DO NOT EDIT THIS FILE! Use an override template instead. *}

<div class="block">
  <label>{'Ini file'|i18n( 'design/standard/class/datatype' )}:</label>
  <input class="box" type="text" name="ContentClass_ymcdynamicinisetting_file_{$class_attribute.id}" value="{$class_attribute.data_text1|wash}" size="30" maxlength="50">
</div>

<div class="block">
  <label>{'Ini Section'|i18n( 'design/standard/class/datatype' )}:</label>
  <input class="box" type="text" name="ContentClass_ymcdynamicinisetting_section_{$class_attribute.id}" value="{$class_attribute.data_text2|wash}" size="30" maxlength="50">
</div>

<div class="block">
  <label>{'Ini Parameter'|i18n( 'design/standard/class/datatype' )}:</label>
  <input class="box" type="text" name="ContentClass_ymcdynamicinisetting_parameter_{$class_attribute.id}" value="{$class_attribute.data_text3|wash}" size="30" maxlength="50">
</div>

<div class="element">
  <label>{'Ini setting type'|i18n( 'design/standard/class/datatype' )}:</label>
  <select name="ContentClass_ymcdynamicinisetting_type_{$class_attribute.id}">
    <option value="1" {section show=$class_attribute.data_int1|eq( 1 )}selected="selected"{/section}>{'Text'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="2" {section show=$class_attribute.data_int1|eq( 2 )}selected="selected"{/section}>{'Enable/Disable'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="3" {section show=$class_attribute.data_int1|eq( 3 )}selected="selected"{/section}>{'True/False'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="4" {section show=$class_attribute.data_int1|eq( 4 )}selected="selected"{/section}>{'Integer'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="5" {section show=$class_attribute.data_int1|eq( 5 )}selected="selected"{/section}>{'Float'|i18n( 'design/standard/class/datatype' )}</option>
    <option value="6" {section show=$class_attribute.data_int1|eq( 6 )}selected="selected"{/section}>{'Array'|i18n( 'design/standard/class/datatype' )}</option>
  </select>
</div>

</div>
