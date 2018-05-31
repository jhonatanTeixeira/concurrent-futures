<?php

namespace Vox\Futures;

use Arara\Process\Child;

class Future implements FutureInterface
{
    /**
     * @var SharedMemory
     */
    private $block;
    
    /**
     * @var Child
     */
    private $child;
    
    private $isCanceled = false;

    public function __construct(SharedMemory $block, Child $child)
    {
        $this->block = $block;
        $this->child = $child;
    }
    
    public function cancel()
    {
        $this->child->kill();
        $this->isCanceled = true;
    }

    public function canceled(): bool
    {
        return $this->isCanceled;
    }

    public function done(): bool
    {
        $status = $this->child->getStatus();
        
        if ($status) {
            return true;
        }
        
        return false;
    }

    public function result()
    {
        while (!$this->done()) {
            continue;
        }
        
        $result = $this->block->read();
        
        return $result;
    }

    public function running(): bool
    {
        if ($this->child->hasId()) {
            return $this->child->isRunning();
        }
        
        return false;
    }
}
