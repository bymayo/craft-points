<?php

namespace bymayo\points\controllers;

use bymayo\points\models\Level;
use bymayo\points\Points;
use Craft;
use craft\helpers\AdminTable;
use craft\helpers\Html;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class LevelsController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-viewLevels');
        return $this->renderTemplate('points/levels/index');
    }

    /**
     * AJAX data source for the VueAdminTable on the Levels index.
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('points-viewLevels');

        $request = Craft::$app->getRequest();
        $page = (int) $request->getParam('page', 1);
        $limit = (int) $request->getParam('per_page', 50);
        $search = $request->getParam('search');

        $all = Points::getInstance()->levels->getAllLevels();

        if (is_string($search) && trim($search) !== '') {
            $needle = strtolower(trim($search));
            $all = array_values(array_filter($all, fn($l) =>
                str_contains(strtolower($l->name), $needle) ||
                str_contains(strtolower($l->handle), $needle)
            ));
        }

        $total = count($all);
        $offset = ($page - 1) * $limit;
        $page = array_slice($all, $offset, $limit);

        $data = array_map(fn(Level $level) => [
            'id' => $level->id,
            'title' => $level->name,
            'url' => $level->getCpEditUrl(),
            'handle' => $level->handle,
            'threshold' => $level->threshold,
            'colour' => $level->colourHex
                ? sprintf(
                    '<span style="display:inline-block; width:14px; height:14px; vertical-align:middle; border-radius:3px; background:%s; border:1px solid rgba(0,0,0,.1); margin-right:6px;"></span><code>%s</code>',
                    Html::encode($level->colourHex),
                    Html::encode($level->colourHex)
                )
                : '',
        ], $page);

        return $this->asSuccess(data: [
            'pagination' => AdminTable::paginationLinks(
                (int) $request->getParam('page', 1),
                $total,
                $limit
            ),
            'data' => $data,
        ]);
    }

    public function actionEdit(?int $levelId = null, ?Level $level = null): Response
    {
        $this->requirePermission('points-viewLevels');

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

        $request = Craft::$app->getRequest();
        $levelId = $request->getBodyParam('levelId');
        $this->requirePermission($levelId ? 'points-editLevels' : 'points-createLevels');

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
        $colour = $request->getBodyParam('colour');
        if ($colour && !str_starts_with($colour, '#')) {
            $colour = '#' . $colour;
        }
        $level->colour = $colour ?: null;

        // `elementSelectField` posts an array of IDs. Take the first; null if empty.
        $iconIds = $request->getBodyParam('icon');
        $iconId = is_array($iconIds) ? ($iconIds[0] ?? null) : $iconIds;
        $level->icon = $iconId ? (string) $iconId : null;

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
        $this->requirePermission('points-deleteLevels');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        Points::getInstance()->levels->deleteLevelById($id);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asSuccess(Craft::t('points', 'Level deleted.'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Level deleted.'));
        return $this->redirect('points/levels');
    }
}
