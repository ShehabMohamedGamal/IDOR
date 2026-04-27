<?php

$legacyVulnerable = filter_var(env('APP_VULNERABLE_IDOR', false), FILTER_VALIDATE_BOOL);
$defaultScenario = $legacyVulnerable ? 'basic_all' : 'safe';
$scenario = env('APP_IDOR_SCENARIO', $defaultScenario);
$allowedScenarios = [
    'safe',
    'basic_all',
    'profile_update_only',
    'hidden_params_review_store',
    'indirect_refs_review_update',
    'uuid_review_update',
];

if (! in_array($scenario, $allowedScenarios, true)) {
    $scenario = 'safe';
}

return [
    'idor_scenario' => $scenario,
    'allowed_idor_scenarios' => $allowedScenarios,
];
