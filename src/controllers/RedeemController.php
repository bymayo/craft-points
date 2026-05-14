<?php

namespace bymayo\points\controllers;

use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class RedeemController extends Controller
{
    protected array|bool|int $allowAnonymous = false;

    public function actionApply(): ?Response
    {
        $this->requirePostRequest();
        $this->requireProCommerce();

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
        $this->requireProCommerce();

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->asFailure('You must be logged in.');
        }

        $orderId = (int) Craft::$app->getRequest()->getRequiredBodyParam('orderId');
        Points::getInstance()->orderRedemptions->remove($orderId);

        return $this->asSuccess('Points removed.');
    }

    /**
     * Order redemptions are a Pro + Commerce feature. Treat any request for
     * a disabled feature as a 404 (don't leak the existence of the endpoint
     * or hint at the edition gating).
     */
    private function requireProCommerce(): void
    {
        $plugin = Points::getInstance();
        if (!$plugin->is(Points::EDITION_PRO) || !$plugin->hasCommerce()) {
            throw new NotFoundHttpException();
        }
    }
}
