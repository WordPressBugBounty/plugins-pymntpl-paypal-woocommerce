<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '1.1.2.x-dev',
        'version' => '1.1.2.9999999-dev',
        'reference' => '070aef77146c216cacece433c2bd2789f57c3976',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.1.2.x-dev',
            'version' => '1.1.2.9999999-dev',
            'reference' => '070aef77146c216cacece433c2bd2789f57c3976',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'paymentplugins/paypal-php-sdk' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '9031390c740f16ec9d493f4a8d7cd9d750c4fc94',
            'type' => 'library',
            'install_path' => __DIR__ . '/../paymentplugins/paypal-php-sdk',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
