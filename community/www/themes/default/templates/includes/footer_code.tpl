{if $T_THEME_SETTINGS->options.show_footer}
	{if $T_CONFIGURATION.custom_footer}
		{eval var = $T_CONFIGURATION.custom_footer}
	{else}	
		<div><a href = "{$smarty.const._EFRONTURL}">{$smarty.const._EFRONTNAME}</a> (version {$smarty.const.G_VERSION_NUM}) &bull; {$T_VERSION_TYPE} Edition &bull; <a href = "index.php?ctg=contact">{$smarty.const._CONTACTUS}</a></div>
	{/if}
{/if}
{if $T_CONFIGURATION.additional_footer}
	<br/>{$T_CONFIGURATION.additional_footer}
{/if}
