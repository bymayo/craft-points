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
            'events' => Points::getInstance()->events->getAllEvents(),
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
        $eventId = (int)$request->getBodyParam('eventId');

        $award->userId = $userId ?: null;
        $award->eventId = $eventId ?: null;

        // Snapshot points from the event at save time. Only set on first save
        // (so editing an award keeps its original points value).
        if (!$award->id && $award->eventId) {
            $event = Points::getInstance()->events->getEventById($award->eventId);
            if ($event) {
                $award->pointsSnapshot = $event->points;
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
