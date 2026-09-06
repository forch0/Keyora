<?php

declare(strict_types=1);

/**
 * Plan limits for Keyora tenants.
 *
 * Used by the usage dashboard (Module 24) to compare actual usage
 * against plan limits. Module 25 (billing) may extend this with
 * pricing and feature flags.
 */
return [

    'free' => [
        'max_members' => 5,
        'max_storage_mb' => 100,
        'max_vault_items' => 100,
    ],

    'team' => [
        'max_members' => 25,
        'max_storage_mb' => 1024,
        'max_vault_items' => 1000,
    ],

    'business' => [
        'max_members' => 100,
        'max_storage_mb' => 10240,
        'max_vault_items' => 10000,
    ],

    'enterprise' => [
        'max_members' => 1000,
        'max_storage_mb' => 102400,
        'max_vault_items' => 100000,
    ],

];
