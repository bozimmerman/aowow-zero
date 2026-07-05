<?php

/*
 * UDWBase: WOWDB Web Interface
 *
 * © UDW 2009-2011
 *
 * Released under the terms and conditions of the
 * GNU General Public License (http://gnu.org).
 *
 */

require_once('includes/game.php');

$char_slots = array(
    0 => 'Head', 1 => 'Neck', 2 => 'Shoulders', 3 => 'Shirt',
    4 => 'Chest', 5 => 'Waist', 6 => 'Legs', 7 => 'Feet',
    8 => 'Wrists', 9 => 'Hands', 10 => 'Finger 1', 11 => 'Finger 2',
    12 => 'Trinket 1', 13 => 'Trinket 2', 14 => 'Back',
    15 => 'Main Hand', 16 => 'Off Hand', 17 => 'Ranged', 18 => 'Tabard'
);

function get_character_list($account_id) {
    global $cDB, $DB;
    if (!$cDB)
        return array();

    $rows = $cDB->select('
        SELECT c.guid, c.name, c.race, c.class, c.gender, c.level, c.online, c.map, c.position_x, c.position_y
        FROM ?_characters c
        WHERE c.account = ?d
        ORDER BY c.name
    ', $account_id);

    if (!$rows)
        return array();

    $result = array();
    foreach ($rows as $row) {
        $zone_info = coord_db2wow($row['map'], $row['position_x'], $row['position_y'], false);
        $row['zone'] = $zone_info ? $zone_info['name'] : 'Unknown';
        $result[] = $row;
    }
    return $result;
}

function get_character_detail($guid, $account_id) {
    global $cDB, $DB;
    if (!$cDB)
        return null;

    $row = $cDB->selectRow('
        SELECT * FROM ?_characters
        WHERE guid = ?d AND account = ?d
    ', $guid, $account_id);

    if (!$row)
        return null;

    $power_labels = array(1 => 'Rage', 2 => 'Mana', 3 => 'Mana', 4 => 'Energy',
        5 => 'Mana', 7 => 'Mana', 8 => 'Mana', 9 => 'Mana', 11 => 'Mana');
    $row['power_type'] = $power_labels[$row['class']] ?? 'Mana';
    $row['power_value'] = (int)($row['power1'] ?? 0);

    $row['money_coin'] = money2coins($row['money']);
    $row['time_played'] = sec_to_time($row['totaltime']);
    $row['time_level'] = sec_to_time($row['leveltime']);

    $row['health'] = (int)($row['health'] ?? 0);
    $row['rest_bonus'] = (float)($row['rest_bonus'] ?? 0);

    $row['total_honor']  = (int)($row['totalHonorPoints'] ?? 0);
    $row['today_honor']  = (int)($row['todayHonorPoints'] ?? 0);
    $row['yday_honor']   = (int)($row['yesterdayHonorPoints'] ?? 0);
    $row['arena_points'] = (int)($row['arenaPoints'] ?? 0);
    $row['total_kills']  = (int)($row['totalKills'] ?? 0);
    $row['today_kills']  = (int)($row['todayKills'] ?? 0);
    $row['yday_kills']   = (int)($row['yesterdayKills'] ?? 0);

    $zone_info = coord_db2wow($row['map'], $row['position_x'], $row['position_y'], false);
    $row['zone'] = $zone_info ? $zone_info['name'] : 'Unknown';

    if (isset($row['equipmentCache']))
        $row['equipment'] = parse_equipment_cache($row['equipmentCache']);
    else
        $row['equipment'] = array();

    $left_slots = array(0, 1, 2, 14, 4, 3, 18, 8);
    $right_slots = array(9, 5, 6, 7, 10, 11, 12, 13);
    $weapon_slots = array(15, 16, 17);
    $row['equipment_left'] = array();
    $row['equipment_right'] = array();
    $row['equipment_weapons'] = array();
    foreach ($left_slots as $s)
        $row['equipment_left'][] = $row['equipment'][$s] ?? array('slotname' => 'Unknown', 'entry' => 0, 'name' => '', 'quality' => 0, 'icon' => '');
    foreach ($right_slots as $s)
        $row['equipment_right'][] = $row['equipment'][$s] ?? array('slotname' => 'Unknown', 'entry' => 0, 'name' => '', 'quality' => 0, 'icon' => '');
    foreach ($weapon_slots as $s)
        $row['equipment_weapons'][] = $row['equipment'][$s] ?? array('slotname' => 'Unknown', 'entry' => 0, 'name' => '', 'quality' => 0, 'icon' => '');

    $row['skills'] = get_character_skills($guid);
    $row['reputation'] = get_character_reputation($guid, $row['race'], $row['class']);
    $row['inventory'] = get_character_inventory($guid);

    $bag_guids = array();
    $bank_bag_guids = array();
    foreach ($row['inventory'] as $item) {
        if ($item['bag'] == 0) {
            if ($item['slot'] >= 19 && $item['slot'] <= 22)
                $bag_guids[$item['guid']] = true;
            elseif ($item['slot'] >= 39 && $item['slot'] <= 45)
                $bank_bag_guids[$item['guid']] = true;
        }
    }

    $row['bags'] = array();
    $row['bank'] = array();
    foreach ($row['inventory'] as $item) {
        if ($item['bag'] == 0) {
            if ($item['slot'] >= 19 && $item['slot'] <= 38)
                $row['bags'][] = $item;
            elseif ($item['slot'] >= 39 && $item['slot'] <= 69)
                $row['bank'][] = $item;
        } elseif (isset($bag_guids[$item['bag']]))
            $row['bags'][] = $item;
        elseif (isset($bank_bag_guids[$item['bag']]))
            $row['bank'][] = $item;
    }
    $row['spells'] = get_character_spells($guid);

    $guild = $cDB->selectRow('
        SELECT g.guildid, g.name AS guild_name, gm.rank
        FROM ?_guild_member gm
        JOIN ?_guild g ON g.guildid = gm.guildid
        WHERE gm.guid = ?d
    ', $guid);
    if ($guild) {
        $row['guild_name'] = $guild['guild_name'];
        $row['guild_rank'] = (int)$guild['rank'];
    }

    return $row;
}

function parse_equipment_cache($blob) {
    global $DB;
    global $char_slots;

    if (!$blob || trim($blob) == '')
        return array();

    $parts = preg_split('/\s+/', trim($blob));
    $result = array();

    $entries = array();
    for ($slot = 0; $slot < 19; $slot++) {
        $entry_idx = $slot * 2;
        $entry = isset($parts[$entry_idx]) ? intval($parts[$entry_idx]) : 0;
        if ($entry > 0)
            $entries[$slot] = $entry;
    }

    $templates = array();
    if ($entries) {
        $all_entries = array_unique(array_values($entries));
        $tpl_rows = $DB->select('
            SELECT entry, name, Quality, iconname, ItemLevel, class, subclass
            FROM ?_aowow_icons, ?_item_template
            WHERE entry IN (?a) AND id = displayid
        ', $all_entries);
        if ($tpl_rows)
            foreach ($tpl_rows as $tr)
                $templates[$tr['entry']] = $tr;
    }

    for ($slot = 0; $slot < 19; $slot++) {
        $entry = isset($entries[$slot]) ? $entries[$slot] : 0;

        if ($entry <= 0) {
            $result[$slot] = array(
                'slot' => $slot,
                'slotname' => isset($char_slots[$slot]) ? $char_slots[$slot] : 'Unknown',
                'entry' => 0,
                'name' => '',
                'quality' => 0,
                'icon' => '',
                'level' => 0,
                'class' => 0,
                'subclass' => 0
            );
            continue;
        }

        $template = isset($templates[$entry]) ? $templates[$entry] : null;

        if ($template) {
            $result[$slot] = array(
                'slot' => $slot,
                'slotname' => isset($char_slots[$slot]) ? $char_slots[$slot] : 'Unknown',
                'entry' => $entry,
                'name' => $template['name'],
                'quality' => $template['Quality'],
                'icon' => $template['iconname'],
                'level' => $template['ItemLevel'],
                'class' => $template['class'],
                'subclass' => $template['subclass']
            );
        } else {
            $result[$slot] = array(
                'slot' => $slot,
                'slotname' => isset($char_slots[$slot]) ? $char_slots[$slot] : 'Unknown',
                'entry' => $entry,
                'name' => '',
                'quality' => 0,
                'icon' => '',
                'level' => 0,
                'class' => 0,
                'subclass' => 0
            );
        }
    }

    return $result;
}

function get_character_skills($guid) {
    global $cDB, $DB;
    if (!$cDB)
        return array();

    $rows = $cDB->select('
        SELECT cs.skill, cs.value, cs.max
        FROM ?_character_skills cs
        WHERE cs.guid = ?d
        ORDER BY cs.value DESC
    ', $guid);

    if (!$rows)
        return array();

    $skill_ids = array();
    foreach ($rows as $row)
        $skill_ids[] = $row['skill'];
    $skill_ids = array_unique($skill_ids);

    $skill_names = array();
    if ($skill_ids) {
        $name_rows = $DB->select('SELECT skillID, name_loc' . $_SESSION['locale'] . ' FROM ?_aowow_skill WHERE skillID IN (?a)', $skill_ids);
        if ($name_rows)
            foreach ($name_rows as $nr)
                $skill_names[$nr['skillID']] = $nr['name_loc' . $_SESSION['locale']];
    }

    $spell_ids = array();
    if ($skill_ids) {
        $spell_rows = $DB->select('SELECT skillID, spellID FROM ?_aowow_skill_line_ability WHERE skillID IN (?a) ORDER BY min_value ASC', $skill_ids);
        if ($spell_rows)
            foreach ($spell_rows as $sr) {
                if (!isset($spell_ids[$sr['skillID']]))
                    $spell_ids[$sr['skillID']] = (int)$sr['spellID'];
            }
    }

    $result = array();
    foreach ($rows as $row) {
        $skill_id = $row['skill'];
        $result[] = array(
            'skill_id' => $skill_id,
            'name' => isset($skill_names[$skill_id]) ? $skill_names[$skill_id] : '',
            'value' => $row['value'],
            'max' => $row['max'],
            'spell_id' => isset($spell_ids[$skill_id]) ? $spell_ids[$skill_id] : 0
        );
    }

    return $result;
}

function get_character_reputation($guid, $race, $class) {
    global $cDB, $DB;
    if (!$cDB)
        return array();

    require_once dirname(__FILE__).'/allreputation.php';

    $raceMask = 1 << ($race - 1);
    $classMask = 1 << ($class - 1);

    $rows = $cDB->select('
        SELECT cr.faction, cr.standing, cr.flags
        FROM ?_character_reputation cr
        WHERE cr.guid = ?d
        ORDER BY cr.standing DESC
        LIMIT 30
    ', $guid);

    if (!$rows)
        return array();

    $standing_names = array(
        0 => 'Hated',
        1 => 'Hostile',
        2 => 'Unfriendly',
        3 => 'Neutral',
        4 => 'Friendly',
        5 => 'Honored',
        6 => 'Revered',
        7 => 'Exalted'
    );

    $points_in_rank = array(36000, 3000, 3000, 3000, 6000, 12000, 21000, 1000);
    $limit = 43000;
    $rank_mins = array();
    for ($i = 7; $i >= 0; $i--) {
        $limit -= $points_in_rank[$i];
        $rank_mins[$i] = $limit;
    }

    $result = array();

    $faction_ids = array();
    foreach ($rows as $row)
        $faction_ids[] = $row['faction'];
    $faction_ids = array_unique($faction_ids);

    $faction_names = array();
    if ($faction_ids) {
        $name_rows = $DB->select('SELECT factionID, name_loc' . $_SESSION['locale'] . ' FROM ?_aowow_factions WHERE factionID IN (?a)', $faction_ids);
        if ($name_rows)
            foreach ($name_rows as $nr)
                $faction_names[$nr['factionID']] = $nr['name_loc' . $_SESSION['locale']];
    }

    foreach ($rows as $row) {
        $faction_id = $row['faction'];
        $faction_name = isset($faction_names[$faction_id]) ? $faction_names[$faction_id] : '';

        $baseRep = 0;
        if (isset($factionDBC[$faction_id])) {
            $dbc = $factionDBC[$faction_id];
            $raceMasks = $dbc[1];
            $classMasks = $dbc[2];
            $values = $dbc[3];
            for ($i = 0; $i < 4; $i++) {
                if (($raceMasks[$i] == 0 || ($raceMasks[$i] & $raceMask)) &&
                    ($classMasks[$i] == 0 || ($classMasks[$i] & $classMask))) {
                    $baseRep = $values[$i];
                    break;
                }
            }
        }

        $total_standing = $row['standing'] + $baseRep;

        $rank = 0;
        for ($i = 7; $i >= 0; $i--) {
            if ($total_standing >= $rank_mins[$i]) {
                $rank = $i;
                break;
            }
        }

        $rank_current = $total_standing - $rank_mins[$rank];
        $rank_max = $points_in_rank[$rank];

        $result[] = array(
            'faction_id' => $faction_id,
            'name' => $faction_name,
            'standing' => $row['standing'],
            'standing_name' => $standing_names[$rank],
            'rank' => $rank,
            'rank_current' => $rank_current,
            'rank_max' => $rank_max
        );
    }

    return $result;
}

function get_character_inventory($guid) {
    global $cDB, $DB;
    if (!$cDB)
        return array();

    $rows = $cDB->select('
        SELECT ci.slot, ci.bag, ci.item AS item_guid, ci.item_template AS entry, ii.data
        FROM ?_character_inventory ci
        LEFT JOIN ?_item_instance ii ON ci.item = ii.guid
        WHERE ci.guid = ?d
        ORDER BY ci.bag, ci.slot
    ', $guid);

    if (!$rows)
        return array();

    $result = array();

    $entries = array();
    foreach ($rows as $row) {
        if (!empty($row['entry']))
            $entries[] = $row['entry'];
    }
    $entries = array_unique($entries);

    $templates = array();
    if ($entries) {
        $tpl_rows = $DB->select('
            SELECT entry, name, Quality, iconname, ItemLevel, RequiredLevel, class, subclass, stackable
            FROM ?_aowow_icons, ?_item_template
            WHERE entry IN (?a) AND id = displayid
        ', $entries);
        if ($tpl_rows)
            foreach ($tpl_rows as $tr)
                $templates[$tr['entry']] = $tr;
    }

    foreach ($rows as $row) {
        $item_guid = $row['item_guid'];
        $entry = $row['entry'];

        if (!$entry)
            continue;

        $stack = 1;
        if (!empty($row['data'])) {
            $fields = explode(' ', $row['data']);
            if (isset($fields[14]))
                $stack = max(1, (int)$fields[14]);
        }

        $template = isset($templates[$entry]) ? $templates[$entry] : null;

        $result[] = array(
            'guid' => $item_guid,
            'bag' => $row['bag'],
            'slot' => $row['slot'],
            'entry' => $entry,
            'name' => $template ? $template['name'] : '',
            'quality' => $template ? $template['Quality'] : 0,
            'icon' => $template ? $template['iconname'] : '',
            'level' => $template ? (int)$template['ItemLevel'] : 0,
            'reqlevel' => $template ? (int)$template['RequiredLevel'] : 0,
            'class' => $template ? $template['class'] : 0,
            'subclass' => $template ? $template['subclass'] : 0,
            'stack' => $stack
        );
    }

    return $result;
}

function get_character_spells($guid) {
    global $cDB, $DB, $allspells;
    if (!$cDB)
        return array();

    $spell_ids = $cDB->selectCol('
        SELECT cs.spell
        FROM ?_character_spell cs
        WHERE cs.guid = ?d
    ', $guid);

    if (!$spell_ids)
        return array();

    $l = $_SESSION['locale'];
    $rows = $DB->select('
        SELECT
            s.spellID,
            s.spellname_loc' . $l . ', s.rank_loc' . $l . ',
            s.levelspell, s.resistancesID,
            s.effect1id, s.effect2id, s.effect3id,
            s.reagent1,
            i.iconname,
            MIN(sla.skillID) AS skillID
        FROM ?_aowow_spell s
        LEFT JOIN ?_aowow_spellicons i ON i.id = s.spellicon
        LEFT JOIN ?_aowow_skill_line_ability sla ON sla.spellID = s.spellID
        WHERE s.spellID IN (?a)
          AND s.spellname_loc' . $l . ' IS NOT NULL
          AND s.spellname_loc' . $l . ' != \'\'
        GROUP BY s.spellID
    ', $spell_ids);

    $result = array();
    foreach ($rows as $row) {
        if (_spell_is_recipe($row))
            continue;

        if ((int)$row['resistancesID'] === 0 && (int)$row['levelspell'] === 0)
            continue;

        $name = trim($row['spellname_loc' . $l]);
        if ($name === '')
            continue;

        $allspells[$row['spellID']] = array(
            'entry' => (int)$row['spellID'],
            'icon'  => trim($row['iconname'], "\r")
        );

        $entry = array();
        $entry['entry']   = (int)$row['spellID'];
        $entry['name']    = '@' . $name;
        $entry['level']   = (int)$row['levelspell'];
        $entry['school']  = (int)$row['resistancesID'];
        $entry['rank']    = trim($row['rank_loc' . $l]);
        if ($row['skillID'])
            $entry['skill'] = array((int)$row['skillID']);
        $result[] = $entry;
    }

    usort($result, function($a, $b) {
        if ($a['level'] != $b['level'])
            return $a['level'] - $b['level'];
        return strcasecmp($a['name'], $b['name']);
    });

    return $result;
}

function _spell_is_recipe($row) {
    if ($row['reagent1'] > 0) {
        for ($j = 1; $j <= 3; $j++)
            if ($row['effect' . $j . 'id'] == 24)
                return true;
    }
    for ($j = 1; $j <= 3; $j++)
        if (in_array($row['effect' . $j . 'id'], array(36, 94)))
            return true;
    return false;
}

function get_character_classes() {
    return array(
        1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue',
        5 => 'Priest', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
    );
}

function get_character_races() {
    return array(
        1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf',
        5 => 'Undead', 6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll'
    );
}
