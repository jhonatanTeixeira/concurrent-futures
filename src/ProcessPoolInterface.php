<?php

namespace Vox\Futures;

/**
 * @author Jhonatan Teixeira <jhonatan.teixeira@gmail.com>
 */
interface ProcessPoolInterface
{
    public function submit(callable $callable, ...$callableArgs): FutureInterface;
    
    public function map(callable $callable, iterable $data): iterable;
    
    public function shutdown(bool $wait = true);
    
    public function wait(FutureInterface ...$futures): iterable;
}
