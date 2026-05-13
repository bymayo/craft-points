<?php

namespace bymayo\points\controllers;

use bymayo\points\models\Level;
use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class LevelsController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-manageLevels');

        return $this->renderTemplate('points/levels/index', [
            'levels' => Points::getInstance()->levels->getAllLevels(),
        ]);
    }

    public function actionEdit(?int $levelId = null, ?Level $level = null): Response
    {
        $this->requirePermission('points-manageLevels');

        if ($level === null) {
            if ($levelId !== null) {
                $level = Points::getInstance()->levels->getLevelById($levelId);
                if (!$level) {
                    throw new NotFoundHttpException('Level not found.');
                }
            } else {
                $level = new Level();
            }
        }

        return $this->renderTemplate('points/levels/_edit', [
            'level' => $level,
            'title' => $level->id
                ? $level->name
                : Craft::t('points', 'New level'),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageLevels');

        $request = Craft::$app->getRequest();
        $levelId = $request->getBodyParam('levelId');

        if ($levelId) {
            $level = Points::getInstance()->levels->getLevelById((int)$levelId);
            if (!$level) {
                throw new NotFoundHttpException('Level not found.');
            }
        } else {
            $level = new Level();
        }

        $level->name = (string)$request->getBodyParam('name', $level->name);
        $level->handle = (string)$request->getBodyParam('handle', $level->handle);
        $level->threshold = (int)$request->getBodyParam('threshold', $level->threshold);
        $level->colour = $request->getBodyParam('colour') ?: null;
        $level->icon = $request->getBodyParam('icon') ?: null;

        if (!Points::getInstance()->levels->saveLevel($level)) {
            return $this->asModelFailure(
                $level,
                Craft::t('points', "Couldn't save level."),
                'level'
            );
        }

        return $this->asModelSuccess(
            $level,
            Craft::t('points', 'Level saved.'),
            'level'
        );
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageLevels');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        Points::getInstance()->levels->deleteLevelById($id);

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Level deleted.'));
        return $this->redirect('points/levels');
    }
}
