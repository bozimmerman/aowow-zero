<?php

/**
 *  mangos-zero aowow
 *
 * @package     mangos.zero
 * @subpackage  mangos.zero.aowow
 * @author      TheLuda <theluda@getmangos.com>
 * @copyright   Copyright (c) 2011 mangos foundation (http://getmangos.com/)
 * @license     http://www.gnu.org/licenses/gpl.html GPL v3
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Настройка шаблонизатора и ДБ
include('includes/kernel.php');

fb('Hello world!');

// Объект шаблонизатора
$smarty = new Smarty_UDWBase('wowhead');

// Имя пользователя и пасс
session_start();

if (IsSet($_COOKIE['remember_me']) and !(IsSet($_SESSION['username']))) {
    $_SESSION['username'] = substr($_COOKIE['remember_me'], 0, strlen($_COOKIE['remember_me']) - 40);
    $_SESSION['shapass'] = substr($_COOKIE['remember_me'], strlen($_COOKIE['remember_me']) - 40, 40);
}

$user = null;
if (IsSet($_SESSION['username']) and IsSet($_SESSION['shapass'])) {
    $user = CheckPwd($_SESSION['username'], $_SESSION['shapass']);
    if (is_array($user) && $user) {
        $_SESSION['userid'] = $user['id'];
        $_SESSION['roles'] = $user['roles'];
        $smarty->assign('user', $user);
    } else {
        $user = null;
    }
}
$smarty->assign('user', $user);

if ($user && isset($cDB)) {
    require_once('includes/allcharacters.php');
    $char_list = get_character_list($user['id']);
    $json_list = array();
    foreach ($char_list as $ch) {
        $json_list[] = array(
            'guid'  => $ch['guid'],
            'name'  => $ch['name'],
            'level' => $ch['level'],
            'class' => $ch['class'],
            'race'  => $ch['race'],
        );
    }
    $smarty->assign('characters_json', json_encode($json_list));
} else {
    $smarty->assign('characters_json', '[]');
}

// Язык сайта
if (!isset($_SESSION['locale']) || !in_array($_SESSION['locale'], array(0, 8)))
    $_SESSION['locale'] = $UDWBaseconf['locale'];

$smarty->assign('locale', $_SESSION['locale']);
$smarty->assign('language', $languages[$smarty->get_template_vars('locale')]);

// Параметры передаваемые скрипту
$queryx = $_SERVER['QUERY_STRING'];
@list($razdel, $podrazdel) = explode('=', $_SERVER['QUERY_STRING'], 2);

// Язык, настройки
$conf_file = $smarty->get_template_vars('language') . '.conf';
$smarty->assign('conf_file', $conf_file);
$smarty->assign('query', $_SERVER['QUERY_STRING']);
$smarty->assign('title', '');

// Параметры страницы
global $page;
$page = array(
    'Mapper' => false,
    'Book' => false,
    'Title' => '',
    'tab' => 0,
    'type' => 0,
    'typeid' => 0,
    'path' => '[]'
);
$smarty->assign('page', $page);

// В зависимости от раздела, выбираем что открывать:

switch ($razdel) {
    case 'locale':
        // Изменение языка сайта
        if (in_array($podrazdel, array(0, 8)))
            $_SESSION['locale'] = $podrazdel;
        header('Location: ' . $_SERVER["HTTP_REFERER"]);
        break;
    case 'account':
        include 'account.php';
        break;
    case 'admin':
        if ($_SESSION['roles'] == 2)
            include 'admin.php';
        else
            include 'main.php';
        break;
    case 'comment':
        include 'comment.php';
        break;
    case 'faction':
        include 'faction.php';
        break;
    case 'factions':
        include 'factions.php';
        break;
    case 'forums&board':
        include 'forum.php';
        break;
    case 'item':
        include 'item.php';
        break;
    case 'items':
        include 'items.php';
        break;
    case 'itemset':
        include 'itemset.php';
        break;
    case 'itemsets':
        include 'itemsets.php';
        break;
    case 'latest':
        include 'latest.php';
        break;
    case 'maps':
        include 'maps.php';
        break;
    case 'npc':
        include 'npc.php';
        break;
    case 'npcs':
        include 'npcs.php';
        break;
    case 'object':
        include 'object.php';
        break;
    case 'objects':
        include 'objects.php';
        break;
    case 'quest':
        include 'quest.php';
        break;
    case 'quests':
        include 'quests.php';
        break;
    case 'search':
        include 'search.php';
        break;
    case 'spell':
        include 'spell.php';
        break;
    case 'spells':
        include 'spells.php';
        break;
    case 'character':
    case 'characters':
        if (!isset($_SESSION['userid'])) {
            header('Location: ?account=signin');
            exit;
        }
        include 'character.php';
        break;
    default:
        include 'main.php';
        break;
}
