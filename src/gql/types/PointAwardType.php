<?php

namespace bymayo\points\gql\types;

use bymayo\points\elements\PointAward;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PointAwardType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsAward',
            'description' => 'A Points award — a single award of points to a user.',
            'fields' => [
                'id' => Type::int(),
                'userId' => Type::int(),
                'ruleId' => Type::int(),
                'pointsSnapshot' => [
                    'type' => Type::int(),
                    'description' => 'Points awarded by this record, snapshotted from the rule at award time.',
                ],
                'dateCreated' => [
                    'type' => Type::string(),
                    'description' => 'ISO 8601 timestamp.',
                    'resolve' => fn(PointAward $award) => $award->dateCreated?->format(\DateTimeInterface::ATOM),
                ],
                'rule' => [
                    'type' => RuleType::getType(),
                    'resolve' => fn(PointAward $award) => $award->getRule(),
                ],
            ],
        ]);

        return self::$type;
    }
}
