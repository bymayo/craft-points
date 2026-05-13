<?php

namespace bymayo\points\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EventType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsEvent',
            'description' => 'A Points event definition.',
            'fields' => [
                'id' => Type::int(),
                'name' => Type::string(),
                'handle' => Type::string(),
                'points' => Type::int(),
                'pointsType' => Type::string(),
                'multiple' => Type::boolean(),
                'trigger' => Type::string(),
                'uid' => Type::string(),
            ],
        ]);

        return self::$type;
    }
}
