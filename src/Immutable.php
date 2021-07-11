<?php

namespace Elazar\Phanua;

trait Immutable
{
    private function with(string $name, $value): self
    {
        $clone = clone $this;
        $clone->$name = $value;
        return $clone;
    }
}
