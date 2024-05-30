<?php

namespace Vox\Futures;

interface FutureInterface
{
    public function cancel();
    
    public function canceled(): bool;
    
    public function running(): bool;
    
    public function done(): bool;
    
    public function result(int $timeout = null);
}
