<?php

namespace bymayo\points\controllers;

use bymayo\points\Points;
use craft\web\Controller;
use yii\web\Response;

class LeaderboardController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageAwards');

        return $this->renderTemplate('points/leaderboard/index', [
            'rows' => Points::getInstance()->awards->leaderboard(50),
        ]);
    }
}
