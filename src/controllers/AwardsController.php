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
        $this->requirePermission('points-viewAwards');
        return $this->renderTemplate('points/awards/index');
    }

    public function actionEdit(?int $awardId = null, ?PointAward $award = null): Response
    {
        $this->requirePermission('points-viewAwards');

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

        $request = Craft::$app->getRequest();
        $awardId = $request->getBodyParam('awardId');
        $this->requirePermission($awardId ? 'points-editAwards' : 'points-createAwards');

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
                $award->pointsSnapshot = match ($rewardType) {
                    'flat' => (int) ($rule->reward['points'] ?? 0),
                    'deduct' => -1 * (int) ($rule->reward['points'] ?? 0),
                    default => 0,
                };
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
        $this->requirePermission('points-deleteAwards');

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        $award = Points::getInstance()->awards->getAwardById($id);
        if ($award) {
            Craft::$app->getElements()->deleteElement($award);
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Award deleted.'));
        return $this->redirect('points/awards');
    }

    /**
     * Frontend JS API: returns the current request's CSRF token.
     * Called by the JS client once before any award POST so we never embed
     * tokens in cached HTML.
     */
    public function actionToken(): Response
    {
        return $this->asJson([
            'token' => Craft::$app->getRequest()->getCsrfToken(),
        ]);
    }

    /**
     * Frontend endpoint: award points to the *currently-logged-in user* for a
     * Manual rule (no trigger). Supports two callers:
     *
     *   1. `<form method="post">` POST — content-negotiated, sets flash and
     *      redirects to `redirectInput()` target.
     *   2. AJAX (sets `Accept: application/json`) — used by the JS API
     *      `window.Points.addAward()` and by anything that wants JSON back.
     *
     * Security boundary (identical to GraphQL `pointsAddAward`):
     *   - Requires a valid Craft session
     *   - Standard Craft CSRF validation
     *   - Manual rules only (rules with a trigger fire on system events)
     *   - User can only ever award points to themselves — `userId` is never
     *     accepted as input
     *   - Rule Limits are enforced
     *   - Active date range is honoured
     */
    public function actionAdd(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $acceptsJson = $request->getAcceptsJson();

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->_failure(
                Craft::t('points', 'You must be logged in to earn points.'),
                $acceptsJson,
            );
        }

        $handle = (string) $request->getRequiredBodyParam('ruleHandle');

        $rule = Points::getInstance()->rules->getRuleByHandle($handle);
        if (!$rule || !$rule->enabled || $rule->handle === '__redemption') {
            return $this->_failure(Craft::t('points', 'Rule not available.'), $acceptsJson);
        }

        // Only Manual rules can be fired via the API. Automatic rules fire on
        // their underlying system events — making them callable would let
        // anyone game them.
        if ($rule->trigger) {
            return $this->_failure(
                Craft::t('points', 'This rule fires automatically and cannot be triggered manually.'),
                $acceptsJson,
            );
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($rule->activeFrom && $now < $rule->activeFrom) {
            return $this->_failure(Craft::t('points', 'Rule is not yet active.'), $acceptsJson);
        }
        if ($rule->activeTo && $now > $rule->activeTo) {
            return $this->_failure(Craft::t('points', 'Rule is no longer active.'), $acceptsJson);
        }

        $award = Points::getInstance()->awards->addAward($user->id, $handle);
        if (!$award) {
            return $this->_failure(
                Craft::t('points', 'Could not award points (limit reached or rule rejected).'),
                $acceptsJson,
            );
        }

        $settings = Points::getInstance()->getSettings();

        if ($acceptsJson) {
            return $this->asJson([
                'success' => true,
                'points' => $award->pointsSnapshot,
                'currency' => $settings->currencyNamePlural,
                'awardId' => $award->id,
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', '{points} {currency} awarded.', [
            'points' => $award->pointsSnapshot,
            'currency' => $settings->currencyNamePlural,
        ]));
        return $this->redirectToPostedUrl();
    }

    /**
     * Frontend endpoint: remove the oldest matching award for the
     * currently-logged-in user. Mirrors `actionAdd` (same content negotiation
     * and security boundary).
     */
    public function actionRemove(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $acceptsJson = $request->getAcceptsJson();

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->_failure(Craft::t('points', 'You must be logged in.'), $acceptsJson);
        }

        $handle = (string) $request->getRequiredBodyParam('ruleHandle');

        $rule = Points::getInstance()->rules->getRuleByHandle($handle);
        if (!$rule || $rule->handle === '__redemption') {
            return $this->_failure(Craft::t('points', 'Rule not available.'), $acceptsJson);
        }

        if ($rule->trigger) {
            return $this->_failure(
                Craft::t('points', 'This rule fires automatically and cannot be reversed via the API.'),
                $acceptsJson,
            );
        }

        $removed = Points::getInstance()->awards->removeAward($user->id, $handle);
        if (!$removed) {
            return $this->_failure(Craft::t('points', 'No award found to remove.'), $acceptsJson);
        }

        if ($acceptsJson) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Award removed.'));
        return $this->redirectToPostedUrl();
    }

    private function _failure(string $error, bool $acceptsJson): Response
    {
        if ($acceptsJson) {
            return $this->asJson(['success' => false, 'error' => $error]);
        }
        Craft::$app->getSession()->setError($error);
        return $this->redirectToPostedUrl();
    }
}
