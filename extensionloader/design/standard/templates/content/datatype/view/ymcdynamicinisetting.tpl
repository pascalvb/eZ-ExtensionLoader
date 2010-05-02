{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{$attribute.content.info.filename|wash()}
<br />
[{$attribute.content.info.section|wash()}]
<br />
{switch match=$attribute.content.info.type}
  {case in=array( 1, 2, 3, 4, 5 )}
    {$attribute.content.live_data.0.ini_parameter|wash()}={$attribute.content.live_data.0.ini_value|wash()}
    <br />
  {/case}
  
  {case match=6}
    {if $attribute.data_int|eq(1)}
      {$attribute.content.info.parameter}[]<br />
    {/if}
    {foreach $attribute.content.live_data as $item}
      {if $item.ini_key|ne('')}
        {if is_numeric($item.ini_key)}
          {$item.ini_parameter|wash()}[]={$item.ini_value|wash()}
        {else}
          {$item.ini_parameter|wash()}[{$item.ini_key|wash()}]={$item.ini_value|wash()}
        {/if}
        <br />
      {/if}
    {/foreach}
  {/case}
{/switch}