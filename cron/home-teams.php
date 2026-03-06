<?php
require_once '../functions.php';

// Same pattern like your home events
$tabs = ['NFL', 'NBA', 'MLB', 'NHL', 'MLS'];

foreach ($tabs as $tab) {

    $teams = getTeamsByCategory($tab);

    if (empty($teams)) {
        continue;
    }

    $output = [];

    if (!empty($teams) && is_array($teams)) {

        foreach ($teams as $team) {

            $output[] = [
                'id'   => (int)($team['id'] ?? 0),
                'name' => $team['text']['name'] ?? '',
                'slug' => strtolower($team['uriComponent'] ?? '')
            ];
        }
    }

    $cacheKey = "teams_{$tab}";
    cache_set($cacheKey, $output);

}

echo "Teams cache complete.\n";