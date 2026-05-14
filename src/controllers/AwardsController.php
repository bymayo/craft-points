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
        $this->requirePermission('points-manageAwards');

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
     * Frontend JS API: award points to the *currently-logged-in user* for a
     * Manual rule (no trigger).
     *
     * Security:
     *   - Requires a valid Craft session (logged in)
     *   - Standard Craft CSRF validation on POST
     *   - Will only fire rules where `trigger` is null (Manual). Auto rules
     *     can never be fired via this endpoint
     *   - Respects the rule's Limits (e.g. Once per user)
     *   - User can only ever award points to themselves — userId is never
     *     accepted as input
     */
    public function actionFire(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'You must be logged in.'),
            ]);
        }

        $handle = (string) Craft::$app->getRequest()->getRequiredBodyParam('ruleHandle');

        $rule = Points::getInstance()->rules->getRuleByHandle($handle);
        if (!$rule || !$rule->enabled || $rule->handle === '__redemption') {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'Rule not available.'),
            ]);
        }

        // Only Manual rules can be fired via the API. Automatic rules fire on
        // their underlying system events — making them JS-callable would let
        // anyone game them.
        if ($rule->trigger) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'This rule fires automatically and cannot be triggered via the API.'),
            ]);
        }

        // Active date range
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($rule->activeFrom && $now < $rule->activeFrom) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'Rule is not yet active.'),
            ]);
        }
        if ($rule->activeTo && $now > $rule->activeTo) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'Rule is no longer active.'),
            ]);
        }

        $award = Points::getInstance()->awards->addAward($user->id, $handle);
        if (!$award) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('points', 'Could not award points (limit reached or rule rejected).'),
            ]);
        }

        $settings = Points::getInstance()->getSettings();
        return $this->asJson([
            'success' => true,
            'points' => $award->pointsSnapshot,
            'currency' => $settings->currencyNamePlural,
            'awardId' => $award->id,
        ]);
    }
}
