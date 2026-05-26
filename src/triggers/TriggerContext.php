<?php

namespace bymayo\points\triggers;

/**
 * Value object returned by a trigger's handleEvent() to describe what was
 * just observed. Null from handleEvent() means "skip this event".
 *
 * `metadata` is an open bag for future dispatcher capabilities so the
 * interface can grow without breaking existing custom triggers.
 */
final class TriggerContext
{
    public function __construct(
        public readonly int $userId,
        public readonly ?float $amount = null,
        public readonly ?int $orderId = null,
        public readonly array $metadata = [],
    ) {}
}
