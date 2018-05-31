# Concurrent Futures

Heavily inspired by python's concurrent future module, this small library tries to abstract away the PHP's pcntl extension
in a simple and robust solution.

## Requirements
* php 7.1 +
* pcntl
* cli SAPI

## Instalation

```
$ composer require vox/concurrent-futures
```

## Usage

```php
// start a pool with a maximum of permited child processes.
//there will be a queue of runnable processes while the pool is full
$pool = new ProcessPool(3);

// map method return an array of future objects
$futures = $pool->map(function ($number) {
    return $number;
}, range(0, 2));

foreach ($futures as $future) {
    // a future object carries the result from the callable, it may throw an exception in case the callable has thrown one
    $result = $future->result();
}

// instead of map, one can submit callables one by one, and manipulate the resulting future object
$future = $pool->submit(function ($number) {
    return $number;
});

$result = $future->result();
```
