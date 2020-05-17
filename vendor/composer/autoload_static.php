<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9e5782e4af4a195586d66c579536261e
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Curl\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Curl\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-curl-class/php-curl-class/src/Curl',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9e5782e4af4a195586d66c579536261e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9e5782e4af4a195586d66c579536261e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}