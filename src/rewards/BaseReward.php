<?php

namespace bymayo\points\rewards;

abstract class BaseReward implements RewardInterface
{
    public static function appliesToSubjects(): ?array
    {
        return null; // applies to all subjects, including Manual rules
    }
}
