<?php

namespace MixPlus\Utils;

use Psr\Container\ContainerInterface;

class ApplicationContext
{
    /**
     * @var ContainerInterface|null
     */
    private static  $container = null;

    /**
     * @throws \TypeError
     */
    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    public static function hasContainer(): bool
    {
        return isset(self::$container);
    }

    public static function setContainer(ContainerInterface $container): ContainerInterface
    {
        self::$container = $container;
        return $container;
    }
}