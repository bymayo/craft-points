<?php

namespace bymayo\points\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PointAwardQuery extends ElementQuery
{
    public mixed $eventId = null;
    public mixed $userId = null;

    public function eventId(mixed $value): self
    {
        $this->eventId = $value;
        return $this;
    }

    public function userId(mixed $value): self
    {
        $this->userId = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('points_awards');

        $this->query->select([
            'points_awards.eventId',
            'points_awards.userId',
            'points_awards.pointsSnapshot',
        ]);

        if ($this->eventId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_awards.eventId', $this->eventId));
        }

        if ($this->userId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_awards.userId', $this->userId));
        }

        return parent::beforePrepare();
    }
}
