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

    private array $context;

    public function __construct(
        LoggerInterface $logger,
        array $context = []
    ) {
        $this->logger = $logger;
        $this->reset($context);
    }

    public function add(array $context)
    {
        $this->context = array_merge($this->context, $context);
    }

    public function reset(array $context = [])
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
