<?php

namespace Elazar\Phanua;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Composes another logger to maintain context as internal state.
 */
class ContextLogger extends AbstractLogger
{
    private LoggerInterface $logger;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        LoggerInterface $logger,
        array $context = []
    ) {
        $this->logger = $logger;
        $this->reset($context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function add(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function reset(array $context = []): void
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->add($context);

        $this->logger->log(
            $level,
            $message,
            $this->context
        );
    }
}
