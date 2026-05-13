<?php

namespace bymayo\points\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class LevelType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsLevel',
            'description' => 'A Points level definition (e.g. Bronze, Silver, Gold).',
            'fields' => [
                'id' => Type::int(),
                'name' => Type::string(),
                'handle' => Type::string(),
                'threshold' => Type::int(),
                'colour' => Type::string(),
                'icon' => Type::string(),
                'uid' => Type::string(),
            ],
        ]);

        return self::$type;
    }
}
