<?php

use MixPlus\Utils\ApplicationContext;
use MixPlus\Utils\Coroutine;

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param null|mixed $default
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }
}

if (! function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param mixed $value
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}


if (! function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param array $array
     */
    function head($array)
    {
        return reset($array);
    }
}
if (! function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param array $array
     */
    function last($array)
    {
        return end($array);
    }
}


if (! function_exists('call')) {
    /**
     * Call a callback with the arguments.
     *
     * @param mixed $callback
     * @return null|mixed
     */
    function call($callback, array $args = [])
    {
        $result = null;
        if ($callback instanceof \Closure) {
            $result = $callback(...$args);
        } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
            $result = $callback(...$args);
        } elseif (is_array($callback)) {
            [$object, $method] = $callback;
            $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
        } else {
            $result = call_user_func_array($callback, $args);
        }
        return $result;
    }
}

if (! function_exists('go')) {
    /**
     * @param callable $callable
     * @return bool|Coroutine
     */
    function go(callable $callable)
    {
        return Coroutine::create($callable) ?: false;
    }
}

if (! function_exists('co')) {
    /**
     * @param callable $callable
     * @return bool|Coroutine
     */
    function co(callable $callable): Coroutine|bool
    {
        return Coroutine::create($callable) ?: false;
    }
}

if (! function_exists('defer')) {
    function defer(callable $callable): void
    {
        Coroutine::defer($callable);
    }
}

if (! function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param object|string $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}


if (! function_exists('make')) {
    /**
     * Create an object instance, if the DI container exist in ApplicationContext,
     * then the object will be created by DI container via `make()` method, if not,
     * the object will create by `new` keyword.
     */
    function make(string $name, array $parameters = [])
    {
        if (ApplicationContext::hasContainer()) {
            $container = ApplicationContext::getContainer();
            if (method_exists($container, 'make')) {
                return $container->make($name, $parameters);
            }
        }
        $parameters = array_values($parameters);
        return new $name(...$parameters);
    }
}

if (! function_exists('run')) {
    /**
     * Run callable in non-coroutine environment, all hook functions by Swoole only available in the callable.
     *
     * @param array|callable $callbacks
     */
    function run($callbacks, int $flags = SWOOLE_HOOK_ALL): bool
    {

        \Swoole\Runtime::enableCoroutine($flags);

        /* @phpstan-ignore-next-line */
        $result = \Swoole\Coroutine\run(...(array) $callbacks);

        \Swoole\Runtime::enableCoroutine(false);
        return $result;
    }
}


use Swoole\Coroutine\WaitGroup;

if (!function_exists('do_parallel')) {
    function do_parallel(array $calls, int $concurrent = 30): array
    {
        $wg = new WaitGroup();
        $results = [];

        $wg->add(count($calls));

        foreach ($calls as $call) {
            Coroutine::create(function () use ($wg, $call, &$results) {
                try {
                    $results[] = call($call);
                } catch (\Throwable $e) {
                    echo '出现并发执行错误: '.$e->getMessage();
                } finally {
                    $wg->done();
                }
            });
        }

        $wg->wait();

        return $results ?? [];
    }
}
