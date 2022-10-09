<?php

namespace MixPlus\Utils;

use ArrayObject;
use MixPlus\Framework\Server\Swow\Exception\CoroutineDestroyedException;
use MixPlus\Utils\Exception\RunningInNonCoroutineException;
use MixPlus\Utils\Exception\RuntimeException;
use \Swoole\Coroutine as SwooleCo;

class Coroutine
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var int|null
     */
    private  $id = null;


    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public static function create(callable $callable, ...$data)
    {
        $coroutine = new static($callable);
        $coroutine->execute(...$data);
        return $coroutine;
    }

    public function execute(...$data)
    {

        $this->id = SwooleCo::create($this->callable, ...$data);
        return $this;
    }

    public function getId(): int
    {
        if (is_null($this->id)) {
            throw new RuntimeException('Coroutine was not be executed.');
        }
        return $this->id;
    }

    public static function id(): int
    {
        return SwooleCo::getCid();
    }

    public static function pid(?int $id = null): int
    {
        if ($id) {
            $cid = SwooleCo::getPcid($id);
            if ($cid === false) {
                throw new CoroutineDestroyedException(sprintf('Coroutine #%d has been destroyed.', $id));
            }
        } else {
            $cid = SwooleCo::getPcid();
        }
        if ($cid === false) {
            throw new RunningInNonCoroutineException('Non-Coroutine environment don\'t has parent coroutine id.');
        }
        return max(0, $cid);
    }

    public static function set(array $config): void
    {
        SwooleCo::set($config);
    }

    public static function getContextFor(?int $id = null): ?ArrayObject
    {
        if ($id === null) {
            return SwooleCo::getContext();
        }

        return SwooleCo::getContext($id);
    }

    public static function defer(callable $callable): void
    {
        SwooleCo::defer($callable);
    }

    /**
     * Yield the current coroutine.
     * @param mixed $data only Support Swow
     * @return bool
     */
    public static function yield($data = null)
    {
        return SwooleCo::yield();
    }

    /**
     * Resume the coroutine by coroutine Id.
     * @param mixed $data only Support Swow
     * @return bool
     */
    public static function resumeById(int $id, ...$data)
    {
        return SwooleCo::resume($id);
    }
}