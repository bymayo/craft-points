<?php

namespace bymayo\points\services;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\events\RegisterRewardsEvent;
use bymayo\points\Points;
use bymayo\points\rewards\DeductReward;
use bymayo\points\rewards\FlatReward;
use bymayo\points\rewards\PercentReward;
use bymayo\points\rewards\RewardInterface;
use yii\base\Component;

class Rewards extends Component
{
    public const EVENT_REGISTER_REWARDS = 'registerRewards';

    /** @var array<string, string> Map of handle → class. */
    private array $_byHandle = [];

    public function init(): void
    {
        parent::init();
        $this->registerRewards();
    }

    /** @return array<string, string> */
    public function getAll(): array
    {
        return $this->_byHandle;
    }

    public function getByHandle(string $handle): ?string
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Calculate the points value of a reward spec.
     * Returns 0 if the reward type isn't registered.
     */
    public function calculate(array $spec, RuleEvaluationContext $ctx): int
    {
        $type = $spec['type'] ?? null;
        if (!$type) return 0;
        $class = $this->_byHandle[$type] ?? null;
        if (!$class) return 0;
        /** @var class-string<RewardInterface> $class */
        return $class::calculate($spec, $ctx);
    }

    private function registerRewards(): void
    {
        $defaults = [
            FlatReward::class,
        ];

        // Pro-only: variable rewards needing a trigger amount.
        if (Points::getInstance()->is(Points::EDITION_PRO)) {
            $defaults[] = PercentReward::class;
        }

        $defaults[] = DeductReward::class;

        $event = new RegisterRewardsEvent(['rewards' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_REWARDS, $event);

        foreach ($event->rewards as $class) {
            if (!is_subclass_of($class, RewardInterface::class)) {
                continue;
            }
            $this->_byHandle[$class::handle()] = $class;
        }
    }
}
