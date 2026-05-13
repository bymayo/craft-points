<?php

namespace bymayo\points\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class LeaderboardRowType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsLeaderboardRow',
            'description' => 'One row of the Points leaderboard.',
            'fields' => [
                'userId' => [
                    'type' => Type::int(),
                    'resolve' => fn(array $row) => $row['user']?->id,
                ],
                'userName' => [
                    'type' => Type::string(),
                    'resolve' => fn(array $row) => $row['user']?->name,
                ],
                'points' => [
                    'type' => Type::int(),
                    'resolve' => fn(array $row) => $row['points'],
                ],
                'level' => [
                    'type' => LevelType::getType(),
                    'resolve' => fn(array $row) => $row['level'],
                ],
            ],
        ]);

        return self::$type;
    }
}
