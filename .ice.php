<?php return [
    'vendor' => 'spb-lan',
    'name' => 'ice-core-fork',
    'namespace' => 'Ifacesoft\Ice\Core\\',
    'alias' => 'Ic',
    'description' => 'Ice Core Module',
    'url' => 'http://ice-core.ifacesoft.iceframework.net',
    'type' => 'module',
    'context' => '',
//    'source' => [
//        'vcs' => 'mercurial',
//        'repo' => 'https://bitbucket.org/ifacesoft/ice-core'
//    ],
//    'authors' => [
//        [
//            'name' => 'dp',
//            'email' => 'denis.a.shestakov@gmail.com'
//        ]
//    ],
    'pathes' => [
        'config' => 'config/',
        'source' => 'source/backend/',
        'resource' => 'source/resource/',
    ],
    'environments' => [
        'prod' => [
            'name' => 'production',
            'pattern' => '/^prod$/',
            'parent' => null,
            'php' => [
                'functions' => [
                    'error_reporting' => E_ALL & ~E_STRICT & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED,
                ],
                'ini_set' => [
                    'display_errors' => 0
                ]
            ],
        ],
        'test' => [
            'name' => 'testing',
            'pattern' => '/^test$/',
            'parent' => 'prod',
            'php' => [
                'functions' => [
                    'error_reporting' => E_ALL,
                ],
                'ini_set' => [
                    'display_errors' => 1
                ],
            ]
        ],
        'dev' => [
            'name' => 'development',
            'pattern' => '/^dev$/',
            'parent' => 'test',
        ],
        'local' => [
            'pattern' => '/^localhost$|^local$/',
            'parent' => 'dev'
        ],
    ],
    'modules' => []
];
