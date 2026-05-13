<?php

namespace bymayo\points\controllers;

use bymayo\points\models\Event;
use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EventsController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageEvents');

        return $this->renderTemplate('points/events/index', [
            'events' => Points::getInstance()->events->getAllEvents(),
        ]);
    }

    public function actionEdit(?int $eventId = null, ?Event $event = null): Response
    {
        $this->requirePermission('points-manageEvents');

        if ($event === null) {
            if ($eventId !== null) {
                $event = Points::getInstance()->events->getEventById($eventId);
                if (!$event) {
                    throw new NotFoundHttpException('Event not found.');
                }
            } else {
                $event = new Event();
            }
        }

        $triggersService = Points::getInstance()->triggers;

        return $this->renderTemplate('points/events/_edit', [
            'event' => $event,
            'title' => $event->id
                ? $event->name
                : Craft::t('points', 'New event'),
            'triggerOptions' => $triggersService->getSelectOptions(),
            'scopedTriggers' => $triggersService->getScopedTriggersForTemplate(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageEvents');

        $request = Craft::$app->getRequest();
        $eventId = $request->getBodyParam('eventId');

        if ($eventId) {
            $event = Points::getInstance()->events->getEventById((int) $eventId);
            if (!$event) {
                throw new NotFoundHttpException('Event not found.');
            }
        } else {
            $event = new Event();
        }

        $event->name = (string) $request->getBodyParam('name', $event->name);
        $event->handle = (string) $request->getBodyParam('handle', $event->handle);
        $event->points = (int) $request->getBodyParam('points', $event->points);
        $event->multiple = (bool) $request->getBodyParam('multiple', $event->multiple);
        $event->trigger = $request->getBodyParam('trigger') ?: null;

        $triggerConfigs = $request->getBodyParam('triggerConfig', []);
        if ($event->trigger && is_array($triggerConfigs) && isset($triggerConfigs[$event->trigger])) {
            $config = $triggerConfigs[$event->trigger];
            $event->triggerConfig = is_array($config) ? $config : null;
        } else {
            $event->triggerConfig = null;
        }

        if (!Points::getInstance()->events->saveEvent($event)) {
            return $this->asModelFailure(
                $event,
                Craft::t('points', "Couldn't save event."),
                'event'
            );
        }

        return $this->asModelSuccess(
            $event,
            Craft::t('points', 'Event saved.'),
            'event'
        );
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageEvents');

        $id = (int) Craft::$app->getRequest()->getRequiredBodyParam('id');
        Points::getInstance()->events->deleteEventById($id);

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Event deleted.'));
        return $this->redirect('points/events');
    }
}
