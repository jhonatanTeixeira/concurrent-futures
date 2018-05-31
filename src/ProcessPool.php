<?php

declare(ticks=1);

namespace Vox\Futures;

use Arara\Process\Action\Callback;
use Arara\Process\Child;
use Arara\Process\Control;
use Arara\Process\Pool;

class ProcessPool implements ProcessPoolInterface
{
    /**
     * @var Pool
     */
    private $pool;
    
    /**
     * @var Control
     */
    private $control;
    
    private $blockId;
    
    public function __construct($processLimit)
    {
        $this->pool    = new Pool($processLimit, true);
        $this->control = new Control();
        $this->blockId = time();
    }
    
    public function submit(callable $callable, ...$callableArgs): FutureInterface
    {
        $blockId = ++$this->blockId;
        
        $wrapper = function () use ($blockId, $callable, $callableArgs) {
            $block = new SharedMemory($blockId);
            $result = $callable(...$callableArgs);
            $block->write($result);
        };
        
        $callback = new Callback($wrapper);
        $child    = new Child($callback, $this->control);
        $future   = new Future(new SharedMemory($blockId), $child);
        
        $this->pool->attach($child);
        
        return $future;
    }
    
    public function map(callable $callable, iterable $data): iterable
    {
        $futures = [];
        
        foreach ($data as $item) {
            $futures[] = $this->submit($callable, $item);
        }
        
        return $futures;
    }
    
    public function shutdown(bool $wait = true)
    {
        if ($wait) {
            return $this->pool->terminate();
        }
        
        return $this->pool->kill();
    }

    public function wait(FutureInterface ...$futures): iterable
    {
        $results = [];
        
        foreach ($futures as $future) {
            $results[] = $future->result();
        }
        
        return $results;
    }
}
