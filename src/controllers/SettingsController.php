<?php

namespace bymayo\points\controllers;

use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class SettingsController extends Controller
{
    public function actionEdit(): Response
    {
        $this->requirePermission('points-manageSettings');

        $plugin = Points::getInstance();
        return $this->renderTemplate('points/settings/edit', [
            'plugin' => $plugin,
            'settings' => $plugin->getSettings(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-manageSettings');

        $request = Craft::$app->getRequest();
        $values = $request->getBodyParam('settings', []);

        $plugin = Points::getInstance();
        if (!$plugin->saveSettings($values)) {
            Craft::$app->getSession()->setError(Craft::t('points', "Couldn't save settings."));
            // Re-render the form with submitted (failed-validation) values.
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $plugin->getSettings(),
            ]);
            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
