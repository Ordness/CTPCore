<?php

namespace Ordness\CTP\objects;

final class Point
{
    public function __construct(private array $areas)
    {
    }

    /**
     * @return array
     */
    public function getAreas(): array
    {
        return $this->areas;
    }
}