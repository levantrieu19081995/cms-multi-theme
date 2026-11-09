<?php

// Tên/mô tả chức năng hệ thống hiển thị trong Feature Registry và bộ chọn menu (definition chỉ giữ khóa, không viết cứng chữ).
return [
    'survey' => [
        'name' => 'Survey',
        'description' => 'Create multi-section surveys, collect public responses, view statistics and export to Excel.',
        'targets' => [
            'index' => 'Survey list',
        ],
    ],
    'career' => [
        'name' => 'Career orientation (RIASEC)',
        'description' => 'A survey type with a Holland code result and suggested majors, plus the "Groups & majors" screen. Needs the Survey feature to be on; when off, surveys of this type and the result page stop working (data is kept).',
    ],
    'contact' => [
        'name' => 'Contact',
        'description' => 'Receive contact messages from the public form and manage them in the administration.',
        'targets' => [
            'index' => 'Contact page',
        ],
    ],
    'multilingual' => [
        'name' => 'Multilingual (Vietnamese/English)',
        'description' => 'Lets public content have additional languages: language switcher, translation tabs and fields in administration. When off only Vietnamese remains; translations already entered are kept.',
    ],
];
