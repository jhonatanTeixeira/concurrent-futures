<?php

namespace Vox\Futures;

use LogicException;
use RuntimeException;
use Throwable;

class Future implements FutureInterface
{
    private bool $isRunning = false;

    private bool $isDone = false;

    private bool $isCanceled = false;

    private $status;

    private int $pid;

    private $result = null;

    private $socket;

    private $isChild = false;

    public function __construct(callable $callable, ...$args)
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($sockets === false) {
            throw new RuntimeException("Failed to create socket pair.");
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGCHLD, function () {
            pcntl_waitpid($this->pid, $this->status, WUNTRACED);

            $this->isRunning = false;
            $this->isDone = true;
        });

        $pid = pcntl_fork();

        if ($pid == -1) {
            throw new RuntimeException("Failed to fork: " . pcntl_strerror(pcntl_get_last_error()));
        } elseif ($pid) {
            // Parent process
            $this->isRunning = true;
            $this->pid = $pid;
            fclose($sockets[0]);
            $this->socket = $sockets[1];
        } else {
            // Child process
            $this->isChild = true;

            fclose($sockets[1]);

            try {
                $result = $callable(...$args);
                fwrite($sockets[0], serialize($result));
            } catch (Throwable $e) {
                fwrite($sockets[0], serialize($e));
            }
            
            fclose($sockets[0]);
            
            exit;
        }
    }

    private function checkChild()
    {
        if ($this->isChild) {
            throw new LogicException("This method cannot be called on a child process");
        }
    }

    public function cancel()
    {
        $this->checkChild();

        if (!$this->isCanceled && $this->isRunning) {
            posix_kill($this->pid, SIGTERM);
            pcntl_waitpid($this->pid, $this->status);
        }

        $this->isCanceled = true;
        $this->isRunning = false;
        $this->isDone = true;
    }

    public function canceled(): bool
    {
        $this->checkChild();

        return $this->isCanceled;
    }

    public function running(): bool
    {
        $this->checkChild();

        return $this->isRunning;
    }

    public function done(): bool
    {
        $this->checkChild();

        return $this->isDone;
    }

    public function result(int $timeout = null)
    {
        $this->checkChild();

        if ($timeout) {
            stream_set_timeout($this->socket, $timeout);
        }

        if ($this->result === null) {
            $data = fgets($this->socket);

            if (stream_get_meta_data($this->socket)['timed_out']) {
                $this->cancel();
                throw new TimeoutException("failed to read return value, timed out!");
            }

            if ($data === false) {
                throw new RuntimeException("Failed to read from socket.");
            }

            $this->result = unserialize($data);
            
            if ($this->result instanceof Throwable) {
                throw $this->result;
            }
        }
        
        return $this->result;
    }

    public function __destruct()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        if (!$this->isChild) {
            pcntl_waitpid($this->pid, $_, WUNTRACED);
        }
    }
}
