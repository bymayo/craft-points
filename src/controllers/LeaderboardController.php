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
        $this->requirePermission('points-viewLeaderboard');
        return $this->renderTemplate('points/leaderboard/index');
    }

    /**
     * AJAX data source for the VueAdminTable on the Leaderboard index.
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('points-viewLeaderboard');

        $request = Craft::$app->getRequest();
        $page = (int) $request->getParam('page', 1);
        $limit = (int) $request->getParam('per_page', 50);
        $offset = ($page - 1) * $limit;

        $plugin = Points::getInstance();
        $awards = $plugin->awards;
        $settings = $plugin->getSettings();
        $showMoney = $plugin->is(Points::EDITION_PRO) && $plugin->hasCommerce();

        $rows = $awards->leaderboard($limit, $offset);
        $total = $awards->getDistinctRecipientCount();

        $data = [];
        foreach ($rows as $i => $row) {
            $level = $row['level'] ?? null;
            $entry = [
                'id' => $row['user']->id,
                'rank' => $offset + $i + 1,
                'title' => $row['user']->name,
                'url' => $row['user']->getCpEditUrl(),
                'level' => $level
                    ? sprintf(
                        '<span style="display:inline-flex;align-items:center;gap:6px;">'
                        . '<span style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;"></span>'
                        . '%s'
                        . '</span>',
                        Html::encode($level->colourHex ?: '#808080'),
                        Html::encode($level->name)
                    )
                    : '',
                'points' => $row['points'],
            ];

            if ($showMoney) {
                $redeemed = $awards->getRedeemedPointsForUser($row['user']->id);
                $entry['availableSpend'] = Html::encode($plugin->formatStoreMoney(
                    Points::pointsToMoney($row['points'], $settings)
                ));
                $entry['redeemed'] = Html::encode($plugin->formatStoreMoney(
                    Points::pointsToMoney($redeemed, $settings)
                ));
            }

            $data[] = $entry;
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
