<?php

namespace bymayo\points\controllers;

use bymayo\points\elements\PointAward;
use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AwardsController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageAwards');
        return $this->renderTemplate('points/awards/index');
    }

    public function actionEdit(?int $awardId = null, ?PointAward $award = null): Response
    {
        $this->requirePermission('points-manageAwards');

        if ($award === null) {
            if ($awardId !== null) {
                $award = Points::getInstance()->awards->getAwardById($awardId);
                if (!$award) {
                    throw new NotFoundHttpException('Award not found.');
                }
            } else {
                $award = new PointAward();
            }
        }

        return $this->renderTemplate('points/awards/_edit', [
            'award' => $award,
            'rules' => Points::getInstance()->rules->getAllRules(),
            'title' => $award->id
                ? Craft::t('points', 'Edit award')
                : Craft::t('points', 'New award'),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageAwards');

        $request = Craft::$app->getRequest();
        $awardId = $request->getBodyParam('awardId');

        if ($awardId) {
            $award = Points::getInstance()->awards->getAwardById((int)$awardId);
            if (!$award) {
                throw new NotFoundHttpException('Award not found.');
            }
        } else {
            $award = new PointAward();
        }

        $userIds = $request->getBodyParam('userId');
        $userId = is_array($userIds) ? (int)($userIds[0] ?? 0) : (int)$userIds;
        $ruleId = (int)$request->getBodyParam('ruleId');

        $award->userId = $userId ?: null;
        $award->ruleId = $ruleId ?: null;

        // Snapshot points from the rule's reward config at save time.
        // Manual awards always use flat — percent rewards need a trigger amount.
        if (!$award->id && $award->ruleId) {
            $rule = Points::getInstance()->rules->getRuleById($award->ruleId);
            if ($rule) {
                $rewardType = $rule->reward['type'] ?? 'flat';
                $points = match ($rewardType) {
                    'flat' => (int) ($rule->reward['points'] ?? 0),
                    'deduct' => -1 * (int) ($rule->reward['points'] ?? 0),
                    default => 0,
                };
                $award->pointsSnapshot = $points;
            }
        }

        if (!Craft::$app->getElements()->saveElement($award)) {
            return $this->asModelFailure(
                $award,
                Craft::t('points', "Couldn't save award."),
                'award'
            );
        }

        return $this->asModelSuccess(
            $award,
            Craft::t('points', 'Award saved.'),
            'award'
        );
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageAwards');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        $award = Points::getInstance()->awards->getAwardById($id);
        if ($award) {
            Craft::$app->getElements()->deleteElement($award);
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Award deleted.'));
        return $this->redirect('points/awards');
    }
}
