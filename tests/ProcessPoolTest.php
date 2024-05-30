<?php

namespace Vox\Futures;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessPoolTest extends TestCase
{
    public function testShouldWaitFuturesReadResultsIncludingNullAndExceptionHandling()
    {
        $pool = new ProcessPool(3);
        
        $futures = $pool->map(fn($number) => $number, range(0, 2));
        
        foreach ($futures as $i => $future) {
            $this->assertIsInt($future->result());
            $this->assertEquals($i, $future->result());
        }
        
        $this->assertCount(3, $futures);

        $this->assertNull($pool->submit(fn() => null)->result());

        $this->expectException(RuntimeException::class);

        $pool->wait(function () {
            throw new RuntimeException();
        });
    }

    public function testMaxProcessesHang()
    {
        $pool = new ProcessPool(3, 4);

        $time = time();

        $futures = $pool->map(
            function ($time) { 
                sleep($time);
            },
            [2, 2, 2, 2]
        );

        $time = time() - $time;

        $this->assertTrue($pool->hasRunningProcesses());

        $pool->wait(...$futures);

        $this->assertGreaterThanOrEqual(1, $time);
    }

    public function testSocketShouldTimeOut()
    {
        $pool = new ProcessPool(1);

        $this->expectException(TimeoutException::class);
        
        $pool->submit(fn() => sleep(5))->result(2);
    }
}
