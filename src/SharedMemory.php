<?php

namespace Vox\Futures;

class SharedMemory
{
    private $id;

    private $resourceId;

    private $perms;

    public function __construct(int $id = null, $perms = 0644)
    {
        $this->id    = $id ?? ftok(__FILE__, 't');
        $this->perms = $perms;

        $this->open();
    }

    private function open(): bool
    {
        if (!$this->resourceId) {
            $this->resourceId = @shmop_open($this->id, 'a', 0, 0);
        }
        
        return false !== $this->resourceId;
    }

    public function read()
    {
        if (!$this->open()) {
            return;
        }

        $size  = shmop_size($this->resourceId);
        $value = shmop_read($this->resourceId, 0, $size);

        return unserialize($value);
    }

    public function write($value)
    {
        if ($this->open()) {
            shmop_delete($this->resourceId);
            shmop_close($this->resourceId);
        }

        $value = serialize($value);
        $size  = mb_strlen($value, 'UTF-8');

        $this->resourceId = @shmop_open($this->id, 'c', $this->perms, $size);
        shmop_write($this->resourceId, $value, 0);
    }
    
    public function __destruct()
    {
        if (false !== $this->resourceId) {
            shmop_close($this->resourceId);
        }
    }
}
