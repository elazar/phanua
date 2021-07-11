<?php

namespace Elazar\Phanua\Schema;

use Elazar\Phanua\Immutable;

/**
 * Runtime configuration for Cycle\Schema\Compiler.
 */
class CompilerConfiguration
{
    use Immutable;

    /**
     * @var Cycle\Schema\GeneratorInterface[]
     */
    private array $generators = [];

    /**
     * @var array
     */
    private array $defaults = [];

    /**
     * @return Cycle\Schema\GeneratorInterface[]
     */
    public function getGenerators(): array
    {
        return $this->generators;
    }

    /**
     * @param Cycle\Schema\GeneratorInterface[] $generators
     */
    public function withGenerators(array $generators): self
    {
        return $this->with('generators', $generators);
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults): self
    {
        return $this->with('defaults', $defaults);
    }
}
