<?php
declare(strict_types=1);

namespace Firehed\LSPHP;

use RuntimeException;

class Process
{
    private const PIPESPEC = [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w'],
    ];

    private $command;
    private $stdin;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function setStdin(string $stdin)
    {
        $this->stdin = $stdin;
    }

    public function execute(callable $callback)
    {
        $pipes = [];
        $process = proc_open($this->command, self::PIPESPEC, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not open process');
        }
        list($stdin, $stdout, $stderr) = $pipes;
        if ($this->stdin) {
            fwrite($stdin, $this->stdin);
        }
        fclose($stdin);
        $out = stream_get_contents($stdout);
        fclose($stdout);
        $err = stream_get_contents($stderr);
        fclose($stderr);
        $ret = proc_close($process);

        $callback($ret, $out, $err);
    }
}
