<?php

namespace JKocik\Laravel\Profiler\LaravelExecution;

use Illuminate\Support\Collection;
use JKocik\Laravel\Profiler\Contracts\ExecutionRequest;

class NullRequest implements ExecutionRequest
{
    /**
     * @return Collection
     */
    public function meta(): Collection
    {
        return collect([
            'type' => null,
            'method' => null,
            'path' => null,
        ]);
    }

    /**
     * @return Collection
     */
    public function data(): Collection
    {
        return collect();
    }
}