{config_load file="$conf_file"}

{include file='header.tpl'}

    <div id="main">
        <div id="main-precontents"></div>
        <div id="main-contents" class="main-contents">
            <script type="text/javascript">
                g_initPath({$page.path});
            </script>

            {if isset($error)}
                <div class="text">
                    <h2>{#Characters#}</h2>
                    <p>{$error}</p>
                </div>
            {elseif empty($character_list)}
                <div class="text">
                    <h2>{#Characters#}</h2>
                    <p>{#No_Characters#}</p>
                </div>
            {else}
                <div id="lv-characters" class="listview"></div>

                <script type="text/javascript">
                    {include file='bricks/character_table.tpl' id='characters' data=$character_list}
                </script>
            {/if}

            <div class="clear"></div>
        </div>
    </div>

{include file='footer.tpl'}
