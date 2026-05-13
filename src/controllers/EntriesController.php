<?php

namespace bymayo\points\controllers;

use bymayo\points\elements\PointEntry;
use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EntriesController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageEntries');
        return $this->renderTemplate('points/entries/index');
    }

    public function actionEdit(?int $entryId = null, ?PointEntry $entry = null): Response
    {
        $this->requirePermission('points-manageEntries');

        if ($entry === null) {
            if ($entryId !== null) {
                $entry = Points::getInstance()->entries->getEntryById($entryId);
                if (!$entry) {
                    throw new NotFoundHttpException('Entry not found.');
                }
            } else {
                $entry = new PointEntry();
            }
        }

        return $this->renderTemplate('points/entries/_edit', [
            'entry' => $entry,
            'events' => Points::getInstance()->events->getAllEvents(),
            'title' => $entry->id
                ? Craft::t('points', 'Edit entry')
                : Craft::t('points', 'New entry'),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageEntries');

        $request = Craft::$app->getRequest();
        $entryId = $request->getBodyParam('entryId');

        if ($entryId) {
            $entry = Points::getInstance()->entries->getEntryById((int)$entryId);
            if (!$entry) {
                throw new NotFoundHttpException('Entry not found.');
            }
        } else {
            $entry = new PointEntry();
        }

        $userIds = $request->getBodyParam('userId');
        $userId = is_array($userIds) ? (int)($userIds[0] ?? 0) : (int)$userIds;
        $eventId = (int)$request->getBodyParam('eventId');

        $entry->userId = $userId ?: null;
        $entry->eventId = $eventId ?: null;

        // Snapshot points from the event at save time. Only set if not already set
        // (so editing an entry keeps its original points value).
        if (!$entry->id && $entry->eventId) {
            $event = Points::getInstance()->events->getEventById($entry->eventId);
            if ($event) {
                $entry->pointsSnapshot = $event->points;
            }
        }

        if (!Craft::$app->getElements()->saveElement($entry)) {
            return $this->asModelFailure(
                $entry,
                Craft::t('points', "Couldn't save entry."),
                'entry'
            );
        }

        return $this->asModelSuccess(
            $entry,
            Craft::t('points', 'Entry saved.'),
            'entry'
        );
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageEntries');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        $entry = Points::getInstance()->entries->getEntryById($id);
        if ($entry) {
            Craft::$app->getElements()->deleteElement($entry);
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Entry deleted.'));
        return $this->redirect('points/entries');
    }
}
