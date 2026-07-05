{strip}
new Listview(
    {ldelim}
    template: 'character',
    id: '{$id}',
    {if (isset($name))}name: LANG.tab_{$name},{/if}
    visibleCols: ['race', 'class', 'level', 'zone', 'online'],
    sort: ['name'],
    data: [
    {foreach from=$data item=curr name=i}
        {ldelim}
            name: '{$curr.name|escape:"javascript"}',
            race: {$curr.race},
            classs: {$curr.class},
            level: {$curr.level},
            zone: '{$curr.zone|escape:"javascript"}',
            online: {$curr.online},
            id: {$curr.guid}
        {rdelim}{if not $smarty.foreach.i.last},{/if}
    {/foreach}
    ]
    {rdelim}
);
{/strip}
