<?php

require_once('includes/game.php');
require_once('includes/allcharacters.php');

global $allspells;

$smarty->config_load($conf_file, 'character');

global $page;

list($razdel, $podrazdel) = explode('=', $_SERVER['QUERY_STRING'], 2);

if (!isset($cDB)) {
    $smarty->assign('characters_json', '[]');
    $smarty->assign('error', $smarty->get_config_vars('Characters_Not_Configured'));
    $smarty->assign('character_list', array());
    $page['Title'] = $smarty->get_config_vars('Characters');
    $page['tab'] = 2;
    $page['type'] = 9;
    $page['path'] = '[2]';
    $smarty->assign('page', $page);
    $smarty->display('characters.tpl');
    exit;
}

if (empty($user['id'])) {
    header('Location: ?account=signin');
    exit;
}

$char_list = get_character_list($user['id']);

if (!empty($podrazdel) && is_numeric($podrazdel)) {
    // Detail view: ?character=GUID
    $guid = (int)$podrazdel;
    $character = get_character_detail($guid, $user['id']);

    if (!$character) {
        $smarty->assign('error', $smarty->get_config_vars('Char_Not_Found'));
        $smarty->assign('character_list', $char_list);
        $page['Title'] = $smarty->get_config_vars('Characters');
        $page['tab'] = 2;
        $page['type'] = 9;
        $page['path'] = '[2]';
        $smarty->assign('page', $page);
        $smarty->display('characters.tpl');
        exit;
    }

    $class_map = get_character_classes();
    $race_map = get_character_races();

    $character['class_name'] = $class_map[$character['class']] ?? 'Unknown';
    $character['race_name'] = $race_map[$character['race']] ?? 'Unknown';

    $char_idx = 1;
    foreach ($char_list as $i => $ch) {
        if ($ch['guid'] == $guid) {
            $char_idx = $i + 1;
            break;
        }
    }

    $page['Title'] = $character['name'] . ' - ' . $smarty->get_config_vars('Characters');
    $page['tab'] = 2;
    $page['type'] = 9;
    $page['typeid'] = $character['guid'];
    $page['path'] = '[2, ' . $char_idx . ']';
    $smarty->assign('page', $page);
    $smarty->assign('character', $character);
    $smarty->assign('allspells', $allspells);
    $smarty->display('charsheet.tpl');
} else {
    // Listing view: ?characters
    $class_map = get_character_classes();
    $race_map = get_character_races();

    foreach ($char_list as &$ch) {
        $ch['class_name'] = $class_map[$ch['class']] ?? 'Unknown';
        $ch['race_name'] = $race_map[$ch['race']] ?? 'Unknown';
    }

    $smarty->assign('character_list', $char_list);

    $page['Title'] = $smarty->get_config_vars('Characters');
    $page['tab'] = 2;
    $page['type'] = 9;
    $page['path'] = '[2]';
    $smarty->assign('page', $page);
    $smarty->display('characters.tpl');
}
