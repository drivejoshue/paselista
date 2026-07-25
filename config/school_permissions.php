<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Roles escolares
    |--------------------------------------------------------------------------
    |
    | El cargo visible de una persona no debe confundirse con su rol técnico.
    | Una directora que administra toda la plataforma puede usar school_admin.
    | Un director operativo usa director.
    |
    */

    'roles' => [
        'school_admin' => [
            'label' => 'Administrador escolar',
            'description' => (
                'Responsable completo de la configuración y operación '
                .'de SchoolPass dentro de la institución.'
            ),
        ],

        'director' => [
            'label' => 'Director',
            'description' => (
                'Responsable académico y operativo. Puede administrar '
                .'la operación escolar, pero no licencia ni herramientas '
                .'técnicas de plataforma.'
            ),
        ],

        'prefect' => [
            'label' => 'Prefecto',
            'description' => (
                'Opera entradas, salidas, búsquedas y registros manuales.'
            ),
        ],

        'kiosk' => [
            'label' => 'Kiosco',
            'description' => (
                'Cuenta restringida al dispositivo y pantalla de escaneo.'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permisos del panel administrativo
    |--------------------------------------------------------------------------
    |
    | Las rutas se resuelven mediante patrones compatibles con Str::is().
    | El orden es importante cuando existan patrones que se superpongan.
    |
    */

    'capabilities' => [
        'role_permissions.view' => [
            'label' => 'Matriz de permisos',
            'group' => 'Consulta',
            'description' => (
                'Consultar la hoja oficial de permisos por rol.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.role-permissions.*',
            ],
        ],

        'dashboard.view' => [
            'label' => 'Dashboard escolar',
            'group' => 'Consulta',
            'description' => 'Consultar indicadores generales de la escuela.',
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.dashboard',
            ],
        ],

        'direction_live.use' => [
            'label' => 'Dirección en vivo',
            'group' => 'Operación',
            'description' => (
                'Consultar el estado operativo y las solicitudes en vivo.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.direction-live.*',
            ],
        ],

        'students.manage' => [
            'label' => 'Alumnos',
            'group' => 'Comunidad escolar',
            'description' => (
                'Crear, consultar y actualizar alumnos, fotografías, '
                .'credenciales e inscripción.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.students.*',
            ],
        ],

        'guardians.manage' => [
            'label' => 'Tutores',
            'group' => 'Comunidad escolar',
            'description' => (
                'Crear y actualizar tutores, relaciones, permisos, '
                .'credenciales y cuentas Family.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.guardians.*',
            ],
        ],

        'school_structure.manage' => [
            'label' => 'Estructura escolar',
            'group' => 'Configuración operativa',
            'description' => (
                'Gestionar ciclos, calendario, áreas, reglas, grupos '
                .'y horarios.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.groups.*',
                'admin.areas.*',
                'admin.area-rules.*',
                'admin.calendar.*',
                'admin.cycles.*',
            ],
        ],

        'devices.manage' => [
            'label' => 'Dispositivos',
            'group' => 'Configuración operativa',
            'description' => (
                'Crear, actualizar, vincular y restablecer dispositivos '
                .'de prefectura y kiosco.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.devices.*',
            ],
        ],

        'cycle_operations.manage' => [
            'label' => 'Promoción e inscripciones',
            'group' => 'Configuración operativa',
            'description' => (
                'Promover alumnos, copiar estructura y administrar '
                .'asignaciones del ciclo.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.promotions.*',
                'admin.cycle-enrollments.*',
            ],
        ],

        'credentials.manage' => [
            'label' => 'Credenciales',
            'group' => 'Operación',
            'description' => (
                'Generar, imprimir y revocar credenciales escolares.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.credentials.*',
            ],
        ],

        'reports.use' => [
            'label' => 'Reportes y exportaciones',
            'group' => 'Consulta',
            'description' => (
                'Consultar reportes, exportar Excel/PDF y revisar '
                .'la bitácora de exportaciones.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.reports.*',
            ],
        ],

        'notices.manage' => [
            'label' => 'Avisos',
            'group' => 'Comunicación',
            'description' => (
                'Crear, editar, publicar y archivar avisos escolares.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.notices.*',
            ],
        ],

        'school_users.manage' => [
            'label' => 'Usuarios institucionales',
            'group' => 'Usuarios',
            'description' => (
                'Gestionar directores, prefectos y kioscos. El controlador '
                .'impide que un director gestione administradores escolares.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.users.*',
            ],
        ],

        'imports.manage' => [
            'label' => 'Importación de alumnos',
            'group' => 'Comunidad escolar',
            'description' => (
                'Descargar plantilla, previsualizar y ejecutar '
                .'importaciones masivas.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.imports.*',
            ],
        ],

        'ai.use' => [
            'label' => 'SchoolPass IA',
            'group' => 'Consulta',
            'description' => (
                'Usar conversaciones, análisis, gráficas, impresión y PDF.'
            ),
            'roles' => [
                'school_admin',
                'director',
            ],
            'routes' => [
                'admin.ai.*',
            ],
        ],

        'license.view' => [
            'label' => 'Licencia escolar',
            'group' => 'Administración sensible',
            'description' => (
                'Consultar información contractual, límites y estado '
                .'de la licencia.'
            ),
            'roles' => [
                'school_admin',
            ],
            'routes' => [
                'admin.license.*',
            ],
        ],

        'technical_tools.use' => [
            'label' => 'Herramientas técnicas',
            'group' => 'Administración sensible',
            'description' => (
                'Ejecutar herramientas internas de mantenimiento '
                .'y diagnóstico.'
            ),
            'roles' => [
                'school_admin',
            ],
            'routes' => [
                'admin.tools.*',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Comportamiento de seguridad
    |--------------------------------------------------------------------------
    */

    'security' => [
        /*
         * El administrador escolar conserva acceso completo a las rutas
         * incluidas dentro del grupo /admin.
         */
        'school_admin_bypass' => true,

        /*
         * Una ruta administrativa nueva que todavía no esté declarada en
         * capabilities queda bloqueada para director.
         */
        'deny_unknown_admin_routes_for_director' => true,
    ],
];
