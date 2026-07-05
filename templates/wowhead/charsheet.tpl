{config_load file="$conf_file"}

{include file='header.tpl'}

	<div id="main">
		<div id="main-precontents"></div>
		<div id="main-contents" class="main-contents">
			<script type="text/javascript">
				g_initPath({$page.path});
			</script>

			<table class="infobox">
				<tr><th>{#Character_Summary#}</th></tr>
				<tr><td>
					<h2>{$character.name|escape:'html'}</h2>
					<b>{#Level#}:</b> {$character.level}<br />
					<b>{#Class#}:</b> {$character.class_name}<br />
					<b>{#Race#}:</b> {$character.race_name}<br />
					{if !empty($character.guild_name)}
						<b>{#Guild#}:</b> {$character.guild_name|escape:'html'}<br />
					{/if}
					{if $character.online}
						<span style="color: #00ff00">&#9679; {#Online#}</span><br />
					{else}
						<span style="color: #888888">&#9679; {#Offline#}</span><br />
					{/if}
					<b>{#Zone#}:</b> {$character.zone|escape:'html'}<br />
					{if !empty($character.time_played)}
						<b>{#Played#}:</b>
						{if isset($character.time_played.h)}{$character.time_played.h}h{/if}
						{if isset($character.time_played.m)}{$character.time_played.m}m{/if}<br />
					{/if}
					{if !empty($character.money_coin)}
						<b>{#Money#}:</b>
						{if isset($character.money_coin.moneygold)}{$character.money_coin.moneygold}g {/if}
						{if isset($character.money_coin.moneysilver)}{$character.money_coin.moneysilver}s {/if}
						{if isset($character.money_coin.moneycopper)}{$character.money_coin.moneycopper}c{/if}<br />
					{/if}
					{if $character.rest_bonus > 0}
						<b>{#RestXP#}:</b> {$character.rest_bonus}%<br />
					{/if}
				</td></tr>
			</table>

			<div style="overflow:hidden">
			<div id="tabs-generic"></div>
			<div id="listview-generic" class="listview"></div>

			<div id="tab-equipment" style="display:none">
				<div style="display:flex; justify-content:center; gap:40px;">
					<table class="icontab">
{foreach from=$character.equipment_left item=e}
						<tr><td style="text-align:right;padding-right:8px;vertical-align:middle;white-space:nowrap">{$e.slotname|escape:'html'}</td><td style="white-space:nowrap">
{if $e.entry > 0}
						<a href="?item={$e.entry}" class="q{$e.quality}">
						<span class="iconmedium" style="display:inline-block;vertical-align:middle;background-image:url(images/icons/medium/{$e.icon|lower|escape:'html'}.png)">
						<span class="tile" style="display:block"></span></span>
						{$e.name|escape:'html'}</a>
{else}
						<span style="color: #888888">---</span>
{/if}
						</td></tr>
{/foreach}
					</table>
					<table class="icontab">
{foreach from=$character.equipment_right item=e}
						<tr><td style="text-align:right;padding-right:8px;vertical-align:middle;white-space:nowrap">{$e.slotname|escape:'html'}</td><td style="white-space:nowrap">
{if $e.entry > 0}
						<a href="?item={$e.entry}" class="q{$e.quality}">
						<span class="iconmedium" style="display:inline-block;vertical-align:middle;background-image:url(images/icons/medium/{$e.icon|lower|escape:'html'}.png)">
						<span class="tile" style="display:block"></span></span>
						{$e.name|escape:'html'}</a>
{else}
						<span style="color: #888888">---</span>
{/if}
						</td></tr>
{/foreach}
					</table>
				</div>
				<div style="display:flex; justify-content:center; margin-top:10px;">
					<table class="icontab">
{foreach from=$character.equipment_weapons item=e}
						<tr><td style="text-align:right;padding-right:8px;vertical-align:middle;white-space:nowrap">{$e.slotname|escape:'html'}</td><td style="white-space:nowrap">
{if $e.entry > 0}
						<a href="?item={$e.entry}" class="q{$e.quality}">
						<span class="iconmedium" style="display:inline-block;vertical-align:middle;background-image:url(images/icons/medium/{$e.icon|lower|escape:'html'}.png)">
						<span class="tile" style="display:block"></span></span>
						{$e.name|escape:'html'}</a>
{else}
						<span style="color: #888888">---</span>
{/if}
						</td></tr>
{/foreach}
					</table>
				</div>
			</div>

			<div id="tab-stats" style="display:none">
				<table class="icontab">
					<tr><td><b>{#Health#}:</b></td><td>{$character.health}</td></tr>
					<tr><td><b>{$character.power_type|escape:'html'}:</b></td><td>{$character.power_value}</td></tr>
				</table>
			</div>

			<div id="tab-skills" style="display:none">
				<table class="icontab">
{if !empty($character.skills)}
	{foreach from=$character.skills item=sk name=skills}
					<tr><td>
	{if $sk.spell_id > 0}
						<a href="?spell={$sk.spell_id}">{$sk.name|escape:'html'}</a>
	{else}
						{$sk.name|escape:'html'}
	{/if}
					</td><td>{$sk.value} / {$sk.max}</td></tr>
	{/foreach}
{else}
					<tr><td colspan="2">No skills learned.</td></tr>
{/if}
				</table>
			</div>

			<div id="tab-reputation" style="display:none">
				<table class="icontab">
{if !empty($character.reputation)}
	{foreach from=$character.reputation item=rep name=reps}
					<tr><td style="white-space:nowrap"><a href="?faction={$rep.faction_id}">{$rep.name|escape:'html'}</a></td><td style="white-space:nowrap">{$rep.rank_current} / {$rep.rank_max} {$rep.standing_name|escape:'html'}</td></tr>
	{/foreach}
{else}
					<tr><td colspan="2">No reputation data.</td></tr>
{/if}
				</table>
			</div>

			<div id="tab-pvp" style="display:none">
				<table class="icontab">
					<tr><td><b>Honor Points:</b></td><td>{$character.total_honor}</td></tr>
					<tr><td><b>Today Honor:</b></td><td>{$character.today_honor}</td></tr>
					<tr><td><b>Yesterday Honor:</b></td><td>{$character.yday_honor}</td></tr>
					<tr><td><b>Arena Points:</b></td><td>{$character.arena_points}</td></tr>
					<tr><td><b>Total Kills:</b></td><td>{$character.total_kills}</td></tr>
					<tr><td><b>Today Kills:</b></td><td>{$character.today_kills}</td></tr>
					<tr><td><b>Yesterday Kills:</b></td><td>{$character.yday_kills}</td></tr>
				</table>
			</div>

			<div id="tab-bags" style="display:none">
				<div id="lv-bags" class="listview"></div>
				<script type="text/javascript">
{foreach from=$character.bags item=inv_item}
					g_items[{$inv_item.entry}] = {ldelim} icon: '{$inv_item.icon|escape:javascript}' {rdelim};
{/foreach}
					new Listview({ldelim} template: 'character_inventory', id: 'bags', data: [
{foreach from=$character.bags item=inv_item name=inv}
						{ldelim} entry: {$inv_item.entry}, name: '{$inv_item.name|escape:javascript}', quality: {$inv_item.quality}, level: {$inv_item.level}, reqlevel: {$inv_item.reqlevel}, classs: {$inv_item.class}, subclass: {$inv_item.subclass}, stack: {$inv_item.stack} {rdelim}{if !$smarty.foreach.inv.last},{/if}
{/foreach}
					] {rdelim});
				</script>
			</div>

			<div id="tab-bank" style="display:none">
				<div id="lv-bank" class="listview"></div>
				<script type="text/javascript">
{foreach from=$character.bank item=inv_item}
					g_items[{$inv_item.entry}] = {ldelim} icon: '{$inv_item.icon|escape:javascript}' {rdelim};
{/foreach}
					new Listview({ldelim} template: 'character_inventory', id: 'bank', data: [
{foreach from=$character.bank item=inv_item name=inv}
						{ldelim} entry: {$inv_item.entry}, name: '{$inv_item.name|escape:javascript}', quality: {$inv_item.quality}, level: {$inv_item.level}, reqlevel: {$inv_item.reqlevel}, classs: {$inv_item.class}, subclass: {$inv_item.subclass}, stack: {$inv_item.stack} {rdelim}{if !$smarty.foreach.inv.last},{/if}
{/foreach}
					] {rdelim});
				</script>
			</div>

			<div id="tab-spells" style="display:none">
				<div id="lv-spells" class="listview"></div>
{if !empty($character.spells)}
				<script type="text/javascript">
					var _ = g_spells;
{foreach from=$allspells key=id item=info}
					_[{$id}]={ldelim}icon: '{$info.icon|escape:javascript}'{rdelim};
{/foreach}
					new Listview({ldelim}template:'spell', id:'spells', visibleCols: ['level', 'school'], hiddenCols: ['reagents','skill'], sort: ['level','name'], data: [
{foreach from=$character.spells item=spell name=spells_item}
						{ldelim}
							name: '{$spell.name|escape:javascript}',
							level: {$spell.level},
							school: {$spell.school},
							rank: '{$spell.rank|escape:javascript}',
{if isset($spell.skill)}
							skill: [{$spell.skill[0]}],
{/if}
							id: {$spell.entry}
						{rdelim}{if !$smarty.foreach.spells_item.last},{/if}
{/foreach}
					] {rdelim});
				</script>
{else}
				<p>No spells learned.</p>
{/if}
			</div>

			<script type="text/javascript">
				var tabsRelated = new Tabs({ldelim} parent: ge('tabs-generic') {rdelim});
				tabsRelated.add('{#Equipment#}',   {ldelim} id: 'equipment'   {rdelim});
				tabsRelated.add('{#Stats#}',       {ldelim} id: 'stats'       {rdelim});
				tabsRelated.add('{#Skills#}',      {ldelim} id: 'skills'       {rdelim});
				tabsRelated.add('{#Reputation#}',  {ldelim} id: 'reputation'  {rdelim});
				tabsRelated.add('{#PvP#}',         {ldelim} id: 'pvp'         {rdelim});
				tabsRelated.add('{#Bags#}',        {ldelim} id: 'bags'        {rdelim});
				tabsRelated.add('{#Bank#}',        {ldelim} id: 'bank'        {rdelim});
				tabsRelated.add('{#Spells#}',      {ldelim} id: 'spells'      {rdelim});
				tabsRelated.flush();
			</script>
			</div>

			<div class="clear"></div>
		</div>
	</div>

{include file='footer.tpl'}
