<?php

use Elazar\Phanua\Schema\CompilerConfiguration;
use Cycle\Schema\GeneratorInterface;

beforeEach(function () {
    $this->config = new CompilerConfiguration();
});

it('has no generators by default', function () {
    $generators = $this->config->getGenerators();
    expect($generators)->toBeArray()->toBeEmpty();
});

it('has no default values by default', function () {
    $defaults = $this->config->getDefaults();
    expect($defaults)->toBeArray()->toBeEmpty();
});

it('allows generator overrides', function () {
    $expected = [ mock(GeneratorInterface::class) ];
    $config = $this->config->withGenerators($expected);
    $generators = $config->getGenerators();
    expect($generators)->toBe($expected);
});

it('allows default overrides', function () {
    $expected = [ 'foo' => 'bar' ];
    $config = $this->config->withDefaults($expected);
    $defaults = $config->getDefaults();
    expect($defaults)->toBe($expected);
});
