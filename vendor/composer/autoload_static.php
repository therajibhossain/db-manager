<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdbeb64ee3ab32c9f2a78a41a17655c63
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'DBManager\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'DBManager\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdbeb64ee3ab32c9f2a78a41a17655c63::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdbeb64ee3ab32c9f2a78a41a17655c63::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
