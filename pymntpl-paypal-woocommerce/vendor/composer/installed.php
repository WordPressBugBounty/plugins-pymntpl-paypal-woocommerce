<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '1.1.1.x-dev',
        'version' => '1.1.1.9999999-dev',
        'reference' => '1dc7af5402b614c9229fd267a142ebaef4915cb9',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.1.1.x-dev',
            'version' => '1.1.1.9999999-dev',
            'reference' => '1dc7af5402b614c9229fd267a142ebaef4915cb9',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'paymentplugins/paypal-php-sdk' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '3c939546a8648a11c73eca016955ce6f3e8a0428',
            'type' => 'library',
            'install_path' => __DIR__ . '/../paymentplugins/paypal-php-sdk',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
