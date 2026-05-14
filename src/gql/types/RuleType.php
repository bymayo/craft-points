<?php

namespace bymayo\points\gql\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class RuleType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsRule',
            'description' => 'A Points rule definition.',
            'fields' => [
                'id' => Type::int(),
                'name' => Type::string(),
                'handle' => Type::string(),
                'trigger' => Type::string(),
                'enabled' => Type::boolean(),
                'activeFrom' => Type::string(),
                'activeTo' => Type::string(),
                'uid' => Type::string(),
            ],
        ]);

        return self::$type;
    }
}
