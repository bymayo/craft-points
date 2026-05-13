<?php

namespace bymayo\points\elements\db;

use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PointEntryQuery extends ElementQuery
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
        $this->joinElementTable('points_entries');

        $this->query->select([
            'points_entries.eventId',
            'points_entries.userId',
            'points_entries.pointsSnapshot',
        ]);

        if ($this->eventId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_entries.eventId', $this->eventId));
        }

        if ($this->userId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_entries.userId', $this->userId));
        }

        return parent::beforePrepare();
    }
}
