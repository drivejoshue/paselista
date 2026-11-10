<?php
declare(strict_types=1);

return [
    'free' => [
        'notices' => [
            'label' => 'Avisos escolares',
            'route' => 'admin.notices.index',
            'icon' => 'megaphone',
            'placement' => 'sidebar',
            'roles' => [
                'school_admin',
                'director',
            ],
        ],
    ],
];
