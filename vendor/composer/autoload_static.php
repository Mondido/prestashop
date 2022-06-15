<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd15229c9986d2c0543d70bb3d7260ac4
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MondidoPayments\\' => 16,
        ),
        'L' => 
        array (
            'League\\ISO3166\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MondidoPayments\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'League\\ISO3166\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/iso3166/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'League\\ISO3166\\Exception\\DomainException' => __DIR__ . '/..' . '/league/iso3166/src/Exception/DomainException.php',
        'League\\ISO3166\\Exception\\ISO3166Exception' => __DIR__ . '/..' . '/league/iso3166/src/Exception/ISO3166Exception.php',
        'League\\ISO3166\\Exception\\OutOfBoundsException' => __DIR__ . '/..' . '/league/iso3166/src/Exception/OutOfBoundsException.php',
        'League\\ISO3166\\Guards' => __DIR__ . '/..' . '/league/iso3166/src/Guards.php',
        'League\\ISO3166\\ISO3166' => __DIR__ . '/..' . '/league/iso3166/src/ISO3166.php',
        'League\\ISO3166\\ISO3166DataProvider' => __DIR__ . '/..' . '/league/iso3166/src/ISO3166DataProvider.php',
        'League\\ISO3166\\ISO3166DataValidator' => __DIR__ . '/..' . '/league/iso3166/src/ISO3166DataValidator.php',
        'MondidoPayments\\AdminDisplay' => __DIR__ . '/../..' . '/src/AdminDisplay.php',
        'MondidoPayments\\AdminOrderActions' => __DIR__ . '/../..' . '/src/AdminOrderActions.php',
        'MondidoPayments\\Api' => __DIR__ . '/../..' . '/src/Api.php',
        'MondidoPayments\\Configuration' => __DIR__ . '/../..' . '/src/Configuration.php',
        'MondidoPayments\\Exception\\ApiError' => __DIR__ . '/../..' . '/src/Exception/ApiError.php',
        'MondidoPayments\\Exception\\EmptyConfigurationValue' => __DIR__ . '/../..' . '/src/Exception/EmptyConfigurationValue.php',
        'MondidoPayments\\Exception\\InvalidConfigurationValue' => __DIR__ . '/../..' . '/src/Exception/InvalidConfigurationValue.php',
        'MondidoPayments\\Exception\\InvalidPaymentMethod' => __DIR__ . '/../..' . '/src/Exception/InvalidPaymentMethod.php',
        'MondidoPayments\\Exception\\InvalidPaymentView' => __DIR__ . '/../..' . '/src/Exception/InvalidPaymentView.php',
        'MondidoPayments\\Hook' => __DIR__ . '/../..' . '/src/Hook.php',
        'MondidoPayments\\Lock' => __DIR__ . '/../..' . '/src/Lock.php',
        'MondidoPayments\\Order' => __DIR__ . '/../..' . '/src/Order.php',
        'MondidoPayments\\SettingsForm' => __DIR__ . '/../..' . '/src/SettingsForm.php',
        'MondidoPayments\\Transaction' => __DIR__ . '/../..' . '/src/Transaction.php',
        'MondidoPayments\\WebhookModuleController' => __DIR__ . '/../..' . '/src/WebhookModuleController.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd15229c9986d2c0543d70bb3d7260ac4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd15229c9986d2c0543d70bb3d7260ac4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd15229c9986d2c0543d70bb3d7260ac4::$classMap;

        }, null, ClassLoader::class);
    }
}
