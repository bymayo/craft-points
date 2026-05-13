<?php

namespace bymayo\points\services;

use bymayo\points\models\Level;
use bymayo\points\Points;
use bymayo\points\records\LevelRecord;
use yii\base\Component;
use yii\base\Exception;

class Levels extends Component
{
    public const EVENT_LEVEL_CHANGED = 'levelChanged';

    /**
     * @return Level[] Levels ordered by threshold ascending.
     */
    public function getAllLevels(): array
    {
        $records = LevelRecord::find()
            ->orderBy(['threshold' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
        return array_map(fn(LevelRecord $r) => $this->createLevelFromRecord($r), $records);
    }

    public function getLevelById(int $id): ?Level
    {
        $record = LevelRecord::findOne(['id' => $id]);
        return $record ? $this->createLevelFromRecord($record) : null;
    }

    public function getLevelByHandle(string $handle): ?Level
    {
        $record = LevelRecord::findOne(['handle' => $handle]);
        return $record ? $this->createLevelFromRecord($record) : null;
    }

    public function saveLevel(Level $level, bool $runValidation = true): bool
    {
        if ($runValidation && !$level->validate()) {
            return false;
        }

        if ($level->id) {
            $record = LevelRecord::findOne(['id' => $level->id]);
            if (!$record) {
                throw new Exception("No level exists with the ID {$level->id}.");
            }
        } else {
            $record = new LevelRecord();
        }

        $record->name = $level->name;
        $record->handle = $level->handle;
        $record->threshold = $level->threshold;
        $record->colour = $level->colour;
        $record->icon = $level->icon;

        if (!$record->save(false)) {
            return false;
        }

        $level->id = $record->id;
        $level->uid = $record->uid;
        return true;
    }

    public function deleteLevelById(int $id): bool
    {
        $record = LevelRecord::findOne(['id' => $id]);
        if (!$record) {
            return false;
        }
        return (bool)$record->delete();
    }

    public function levelForUser(int $userId): ?Level
    {
        $sum = Points::getInstance()->awards->sumForUser($userId);
        return $this->levelForPoints($sum);
    }

    /**
     * Returns the highest level whose threshold is ≤ the given point total.
     * Tiebreaker on equal thresholds: most recently created wins (id DESC).
     */
    public function levelForPoints(int $points): ?Level
    {
        $record = LevelRecord::find()
            ->where(['<=', 'threshold', $points])
            ->orderBy(['threshold' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(1)
            ->one();
        return $record ? $this->createLevelFromRecord($record) : null;
    }

    private function createLevelFromRecord(LevelRecord $r): Level
    {
        $level = new Level();
        $level->id = (int)$r->id;
        $level->name = (string)$r->name;
        $level->handle = (string)$r->handle;
        $level->threshold = (int)$r->threshold;
        $level->colour = $r->colour;
        $level->icon = $r->icon;
        $level->uid = $r->uid;
        return $level;
    }
}
