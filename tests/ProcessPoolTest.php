<?php

namespace Vox\Futures;

class ProcessPoolTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldWaitFutures()
    {
        $pool = new ProcessPool(3);
        
        $futures = $pool->map(function ($number) {
            return $number;
        }, range(0, 2));
        
        foreach ($futures as $future) {
            $this->assertInternalType('int', $future->result());
        }
        
        $this->assertCount(3, $futures);
    }
}
