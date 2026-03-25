<?php
require_once '../functions.php';

// Same pattern like your home events
$tabs = ['NFL', 'NBA', 'MLB', 'NHL', 'MLS'];






foreach ($tabs as $tab) {

    $teams = getTeamsByCategory($tab, 100);

    $output = [];    

    if (!empty($teams)) {
        foreach ($teams as $team) {
            $name = $team['text']['name'] ?? '';
            $uri  = $team['uriComponent'] ?? '';
            $id   = $team['id'] ?? null;
            $teamInfo = getTeamImage($name);
           
            if ($name === '' || $uri === '') continue;

            if(!empty($teamInfo)) {
                $output[] = [
                    'id'   => $id,
                    'name' => $name,
                    'slug' => strtolower($uri),
                    'logo' => $teamInfo[0],
                    'loc'  => $teamInfo[1]
                ];
            }
        }
    }

    
    $cacheKey = "teams_{$tab}";
    cache_set($cacheKey, $output);
    

}

echo "home teams cache refreshed on " . date('Y-m-d H:i:s');