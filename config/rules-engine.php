<?php

return [
    'subject_model' => null,

    'scope_model' => null,

    'subject_resolver' => null,

    'scope_resolver' => null,

    'attribute_map' => [
        'date_of_birth' => 'date_of_birth',
        'last_login_at' => 'last_login_at',
    ],

    'member_meta' => [
        'pivot_table' => null,
        'scope_foreign_key' => 'family_group_id',
        'subject_foreign_key' => 'user_id',
        'meta_column' => 'meta',
    ],

    'scope_settings_column' => 'settings',

    'can_manage_rules' => null,

    'notify_scope_admins' => null,

    'create_reminder' => null,

    'run_on_maintain_in_manual' => true,

    'navigation_group' => 'Insights',

    'table_prefix' => 're_',

    'schedule_description' => null,

    'schedule_hour' => 1,

    'schedule_minute' => 15,

    'next_run_resolver' => null,
];
