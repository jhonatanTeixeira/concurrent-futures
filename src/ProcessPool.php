<?php

namespace Vox\Futures;

class ProcessPool implements ProcessPoolInterface
{
    /**
     * @var Future[]
     */
    private array $futures = [];

    public function __construct(
        private int $maxProcess = 0,
        private int $maxWaitSeconds = 10,
    ) {}

    public function submit(callable $callable, ...$callableArgs): FutureInterface
    {
        $waitSeconds = $this->maxWaitSeconds;

        while (
            $this->maxProcess > 0 
            && count($this->getRunningProcesses()) >= $this->maxProcess 
            && $waitSeconds--
        ) {
            sleep(1);
        }

        $this->futures[] = $future = new Future($callable, ...$callableArgs);

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
        if (!$wait) {
            array_walk($this->futures, fn($f) => $f->cancel());
        } else {
            while ($this->hasRunningProcesses()) {
                sleep(1);
            }
        }

        $this->futures = [];
    }
    
    public function wait(FutureInterface | callable ...$futures)
    {
        $results = [];

        foreach ($futures as $future) {
            if (is_callable($future)) {
                $future = $this->submit($future);
            }

            $results[] = $future->result();
        }

        return count($results) > 1 ? $results : $results[0];
    }

    private function getRunningProcesses()
    {
        return $this->futures = array_filter($this->futures, fn($f) => !$f->done());
    }

    public function hasRunningProcesses(): bool
    {
        return count($this->getRunningProcesses()) > 0;
    }
}
