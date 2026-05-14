<?php

namespace bymayo\points\controllers;

use bymayo\points\Points;
use Craft;
use craft\helpers\AdminTable;
use craft\helpers\Html;
use craft\web\Controller;
use yii\web\Response;

class LeaderboardController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageAwards');
        return $this->renderTemplate('points/leaderboard/index');
    }

    /**
     * AJAX data source for the VueAdminTable on the Leaderboard index.
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('points-manageAwards');

        $request = Craft::$app->getRequest();
        $page = (int) $request->getParam('page', 1);
        $limit = (int) $request->getParam('per_page', 50);
        $offset = ($page - 1) * $limit;

        $awards = Points::getInstance()->awards;
        $rows = $awards->leaderboard($limit, $offset);
        $total = $awards->getDistinctRecipientCount();

        $data = [];
        foreach ($rows as $i => $row) {
            $level = $row['level'] ?? null;
            $data[] = [
                'id' => $row['user']->id,
                'rank' => $offset + $i + 1,
                'title' => $row['user']->name,
                'url' => $row['user']->getCpEditUrl(),
                'level' => $level
                    ? sprintf(
                        '<span style="display:inline-block; padding:2px 8px; border-radius:10px; color:#fff; background:%s; font-size:12px;">%s</span>',
                        Html::encode($level->colourHex ?: '#666'),
                        Html::encode($level->name)
                    )
                    : '',
                'points' => $row['points'],
            ];
        }

        return $this->asSuccess(data: [
            'pagination' => AdminTable::paginationLinks(
                (int) $request->getParam('page', 1),
                $total,
                $limit
            ),
            'data' => $data,
        ]);
    }
}
