<?php

namespace bymayo\points\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PointAwardQuery extends ElementQuery
{
    public mixed $ruleId = null;
    public mixed $userId = null;
    public mixed $orderId = null;

    public function ruleId(mixed $value): self
    {
        $this->ruleId = $value;
        return $this;
    }

    public function userId(mixed $value): self
    {
        $this->userId = $value;
        return $this;
    }

    public function orderId(mixed $value): self
    {
        $this->orderId = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('points_awards');

        $this->query->select([
            'points_awards.ruleId',
            'points_awards.userId',
            'points_awards.orderId',
            'points_awards.pointsSnapshot',
        ]);

        if ($this->ruleId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_awards.ruleId', $this->ruleId));
        }

        if ($this->userId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_awards.userId', $this->userId));
        }

        if ($this->orderId !== null) {
            $this->subQuery->andWhere(Db::parseParam('points_awards.orderId', $this->orderId));
        }

        return parent::beforePrepare();
    }
}
