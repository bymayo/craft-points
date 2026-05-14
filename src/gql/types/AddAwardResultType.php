<?php

namespace bymayo\points\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AddAwardResultType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsAddAwardResult',
            'description' => 'Result of a `pointsAddAward` mutation.',
            'fields' => [
                'success' => Type::boolean(),
                'error' => Type::string(),
                'points' => [
                    'type' => Type::int(),
                    'description' => 'Number of points awarded (snapshot of the rule\'s reward at fire time).',
                ],
                'currency' => [
                    'type' => Type::string(),
                    'description' => 'Configured plural currency label (e.g. "Points", "Coins").',
                ],
                'awardId' => Type::int(),
            ],
        ]);

        return self::$type;
    }
}
