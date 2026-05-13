<?php

namespace bymayo\points\gql\types;

use bymayo\points\elements\PointEntry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PointEntryType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type !== null) {
            return self::$type;
        }

        self::$type = new ObjectType([
            'name' => 'PointsEntry',
            'description' => 'A Points entry — a single award of points to a user.',
            'fields' => [
                'id' => Type::int(),
                'userId' => Type::int(),
                'eventId' => Type::int(),
                'pointsSnapshot' => [
                    'type' => Type::int(),
                    'description' => 'Points awarded by this entry, snapshotted from the event at award time.',
                ],
                'dateCreated' => [
                    'type' => Type::string(),
                    'description' => 'ISO 8601 timestamp.',
                    'resolve' => fn(PointEntry $entry) => $entry->dateCreated?->format(\DateTimeInterface::ATOM),
                ],
                'event' => [
                    'type' => EventType::getType(),
                    'resolve' => fn(PointEntry $entry) => $entry->getEvent(),
                ],
            ],
        ]);

        return self::$type;
    }
}
