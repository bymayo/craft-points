<?php

namespace bymayo\points\controllers;

use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class RedeemController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public function actionApply(): ?Response
    {
        $this->requirePostRequest();
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->asFailure('You must be logged in.');
        }

        $request = Craft::$app->getRequest();
        $orderId = (int) $request->getRequiredBodyParam('orderId');
        $points = (int) $request->getRequiredBodyParam('points');

        $result = Points::getInstance()->orderRedemptions->apply($orderId, $user->id, $points);

        if (!$result['success']) {
            return $this->asFailure($result['error'] ?? 'Could not apply redemption.');
        }
        return $this->asSuccess('Points applied.');
    }

    public function actionRemove(): ?Response
    {
        $this->requirePostRequest();
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->asFailure('You must be logged in.');
        }

        $orderId = (int) Craft::$app->getRequest()->getRequiredBodyParam('orderId');
        Points::getInstance()->orderRedemptions->remove($orderId);

        return $this->asSuccess('Points removed.');
    }
}
