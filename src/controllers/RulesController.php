<?php

namespace bymayo\points\controllers;

use bymayo\points\models\Rule;
use bymayo\points\Points;
use Craft;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class RulesController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requirePermission('points-viewRules');
        return $this->renderTemplate('points/rules/index');
    }

    /**
     * AJAX data source for the VueAdminTable on the Rules index.
     */
    public function actionTableData(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('points-viewRules');

        $request = Craft::$app->getRequest();
        $page = (int) $request->getParam('page', 1);
        $limit = (int) $request->getParam('per_page', 50);
        $search = $request->getParam('search');

        $all = Points::getInstance()->rules->getAllRules();

        if (is_string($search) && trim($search) !== '') {
            $needle = strtolower(trim($search));
            $all = array_values(array_filter($all, fn($r) =>
                str_contains(strtolower($r->name), $needle) ||
                str_contains(strtolower($r->handle), $needle)
            ));
        }

        $total = count($all);
        $offset = ($page - 1) * $limit;
        $page = array_slice($all, $offset, $limit);

        $data = array_map(fn(\bymayo\points\models\Rule $rule) => [
            'id' => $rule->id,
            'title' => $rule->name,
            'url' => $rule->getCpEditUrl(),
            'handle' => $rule->handle,
            'trigger' => $rule->triggerLabel,
            'award' => $rule->rewardSummary,
            'limit' => $rule->limitSummary,
            'schedule' => $rule->scheduleSummary,
            'status' => (string) $rule->getStatusLabelHtml(),
        ], $page);

        return $this->asSuccess(data: [
            'pagination' => \craft\helpers\AdminTable::paginationLinks($request->getParam('page', 1), $total, $limit),
            'data' => $data,
        ]);
    }

    public function actionEdit(?int $ruleId = null, ?Rule $rule = null): Response
    {
        $this->requirePermission('points-viewRules');

        if ($rule === null) {
            if ($ruleId !== null) {
                $rule = Points::getInstance()->rules->getRuleById($ruleId);
                if (!$rule) {
                    throw new NotFoundHttpException('Rule not found.');
                }
            } else {
                $rule = new Rule();
            }
        }

        $points = Points::getInstance();
        $conditionMeta = $points->conditions->getMetaForJs();
        $triggerSubjects = $this->buildTriggerSubjects($points->triggers->getAllTriggers());
        $currentSubject = $rule->trigger ? ($triggerSubjects[$rule->trigger] ?? null) : null;
        $hasApplicableConditions = $currentSubject !== null
            && !empty(array_filter(
                $conditionMeta,
                fn(array $m) => in_array($currentSubject, $m['subjects'] ?? [], true)
            ));

        return $this->renderTemplate('points/rules/_edit', [
            'rule' => $rule,
            'title' => $rule->id ? $rule->name : Craft::t('points', 'New rule'),
            'triggerOptions' => $points->triggers->getSelectOptions(),
            'triggerSubjects' => $triggerSubjects,
            'conditionTypes' => $this->buildConditionTypeOptions($points->conditions->getAll()),
            'conditionMeta' => $conditionMeta,
            'hasApplicableConditions' => $hasApplicableConditions,
            'limitTypes' => $this->buildTypeOptions($points->limits->getAll()),
            'rewardTypes' => $this->buildRewardTypeOptions($points->rewards->getAll()),
            'sectionOptions' => $this->elementOptions(
                Craft::$app->getEntries()->getAllSections()
            ),
            'userGroupOptions' => $this->elementOptions(
                Craft::$app->getUserGroups()->getAllGroups()
            ),
            'levelOptions' => array_map(
                fn($l) => ['label' => $l->name, 'value' => $l->handle],
                $points->levels->getAllLevels()
            ),
            'activeFromDt' => $rule->activeFrom ? new \DateTime($rule->activeFrom) : null,
            'activeToDt' => $rule->activeTo ? new \DateTime($rule->activeTo) : null,
            'isPro' => $points->is(Points::EDITION_PRO),
            'conditionProductLookup' => $this->buildConditionProductLookup($rule->conditions),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $ruleId = $request->getBodyParam('ruleId');
        $this->requirePermission($ruleId ? 'points-editRules' : 'points-createRules');

        if ($ruleId) {
            $rule = Points::getInstance()->rules->getRuleById((int) $ruleId);
            if (!$rule) {
                throw new NotFoundHttpException('Rule not found.');
            }
        } else {
            $rule = new Rule();
        }

        $rule->name = (string) $request->getBodyParam('name', $rule->name);
        $rule->handle = (string) $request->getBodyParam('handle', $rule->handle);
        $rule->enabled = (bool) $request->getBodyParam('enabled', true);
        $rule->trigger = $request->getBodyParam('trigger') ?: null;
        $rule->activeFrom = $this->normaliseDateTime($request->getBodyParam('activeFrom'));
        $rule->activeTo = $this->normaliseDateTime($request->getBodyParam('activeTo'));

        $rule->conditions = $this->sanitizeSpecs($request->getBodyParam('conditions', []));
        $rule->limits = $this->sanitizeSpecs($request->getBodyParam('limits', []));

        $reward = $request->getBodyParam('reward', []);
        $rule->reward = is_array($reward) && !empty($reward['type'])
            ? $this->normaliseValues($reward)
            : ['type' => 'flat', 'points' => 0];

        if (!Points::getInstance()->rules->saveRule($rule)) {
            return $this->asModelFailure(
                $rule,
                Craft::t('points', "Couldn't save rule."),
                'rule'
            );
        }

        return $this->asModelSuccess(
            $rule,
            Craft::t('points', 'Rule saved.'),
            'rule'
        );
    }

    /**
     * AJAX: render a single condition or limit row, returning HTML + head/body scripts
     * so the client can wire up Craft UI primitives (element-selects, menu buttons, etc.).
     */
    public function actionAddRow(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        // Either tier can add rows — they're a helper for the rule builder.
        $this->requirePermission('points-viewRules');

        $request = Craft::$app->getRequest();
        $section = (string) $request->getRequiredBodyParam('section');
        $index = (int) $request->getRequiredBodyParam('index');

        if (!in_array($section, ['conditions', 'limits'], true)) {
            throw new \yii\web\BadRequestHttpException('Invalid section.');
        }

        $points = Points::getInstance();
        $view = Craft::$app->getView();
        // Reset registered head/body buffers so we only capture this row's scripts.
        $view->clear();

        $html = $view->renderTemplate('points/rules/_row', [
            'section' => $section,
            'index' => $index,
            'conditionTypes' => $this->buildConditionTypeOptions($points->conditions->getAll()),
            'limitTypes' => $this->buildTypeOptions($points->limits->getAll()),
            'sectionOptions' => $this->elementOptions(Craft::$app->getEntries()->getAllSections()),
            'userGroupOptions' => $this->elementOptions(Craft::$app->getUserGroups()->getAllGroups()),
            'levelOptions' => array_map(
                fn($l) => ['label' => $l->name, 'value' => $l->handle],
                $points->levels->getAllLevels()
            ),
        ]);

        return $this->asJson([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('points-deleteRules');

        $id = (int) Craft::$app->getRequest()->getRequiredBodyParam('id');
        Points::getInstance()->rules->deleteRuleById($id);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asSuccess(Craft::t('points', 'Rule deleted.'));
        }

        Craft::$app->getSession()->setNotice(Craft::t('points', 'Rule deleted.'));
        return $this->redirect('points/rules');
    }

    /**
     * Parse the value submitted by `forms.dateTimeField` (an array like
     * `['date' => 'Y-m-d', 'time' => 'H:i', 'timezone' => '…']`) — or a plain
     * string from the legacy `datetime-local` input — into a MySQL datetime.
     */
    private function normaliseDateTime(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            $dt = \craft\helpers\DateTimeHelper::toDateTime($value);
            return $dt ? $dt->format('Y-m-d H:i:s') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Convert a list of objects with `name` and `id` into option arrays
     * suitable for Craft's `forms.checkboxSelectField`.
     *
     * @param iterable<object> $items
     * @return array<int, array{label: string, value: string}>
     */
    private function elementOptions(iterable $items): array
    {
        $options = [];
        foreach ($items as $item) {
            $options[] = ['label' => $item->name, 'value' => (string) $item->id];
        }
        return $options;
    }

    /**
     * @param array<string, string> $byHandle
     * @return array<int, array{label: string, value: string}>
     */
    private function buildTypeOptions(array $byHandle): array
    {
        $options = [];
        foreach ($byHandle as $handle => $class) {
            $options[] = [
                'label' => $class::label(),
                'value' => $handle,
            ];
        }
        return $options;
    }

    /**
     * Pre-fetch product elements referenced by any `commerce.containsProduct`
     * conditions on this rule, indexed by the condition's position.
     *
     * @param array<int, array<string, mixed>> $conditions
     * @return array<int, array<int, \craft\base\ElementInterface>>
     */
    private function buildConditionProductLookup(array $conditions): array
    {
        $lookup = [];
        if (!class_exists('craft\\commerce\\elements\\Product')) {
            return $lookup;
        }
        foreach ($conditions as $i => $cond) {
            if (($cond['type'] ?? '') !== 'commerce.containsProduct') {
                continue;
            }
            $ids = $cond['productIds'] ?? [];
            if (!is_array($ids)) {
                $ids = array_filter(array_map(
                    fn($s) => (int) trim($s),
                    explode(',', (string) $ids),
                ));
            }
            $ids = array_map('intval', array_filter($ids));
            $lookup[$i] = $ids
                ? \craft\commerce\elements\Product::find()->id($ids)->all()
                : [];
        }
        return $lookup;
    }

    /**
     * Map of trigger handle → subject, used by JS to derive the current subject
     * for condition filtering when the user changes the trigger select.
     *
     * @param string[] $triggerClasses
     * @return array<string, string>
     */
    private function buildTriggerSubjects(array $triggerClasses): array
    {
        $map = [];
        foreach ($triggerClasses as $class) {
            $map[$class::handle()] = $class::subject();
        }
        return $map;
    }

    /**
     * Condition options with subject metadata baked in for the template to render
     * as data-subjects attributes.
     *
     * @param array<string, string> $byHandle
     * @return array<int, array{label: string, value: string, subjects: string}>
     */
    private function buildConditionTypeOptions(array $byHandle): array
    {
        $options = [];
        foreach ($byHandle as $handle => $class) {
            $subjects = $class::appliesToSubjects();
            $options[] = [
                'label' => $class::label(),
                'value' => $handle,
                'subjects' => is_array($subjects) ? implode(',', $subjects) : '',
            ];
        }
        return $options;
    }

    /**
     * Reward options with subject metadata. Same shape as
     * {@see buildConditionTypeOptions} so the template can filter rewards by
     * the active trigger's subject (e.g. only show "% of order total" on
     * Order triggers).
     *
     * @param array<string, string> $byHandle
     * @return array<int, array{label: string, value: string, subjects: string}>
     */
    private function buildRewardTypeOptions(array $byHandle): array
    {
        $options = [];
        foreach ($byHandle as $handle => $class) {
            $subjects = $class::appliesToSubjects();
            $options[] = [
                'label' => $class::label(),
                'value' => $handle,
                'subjects' => is_array($subjects) ? implode(',', $subjects) : '',
            ];
        }
        return $options;
    }

    /**
     * @param array<int, array<string, mixed>> $raw Spec rows from the form.
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeSpecs(array $raw): array
    {
        $clean = [];
        foreach ($raw as $row) {
            if (!is_array($row) || empty($row['type'])) {
                continue;
            }
            $clean[] = $this->normaliseValues($row);
        }
        return array_values($clean);
    }

    /**
     * Coerce numeric strings to ints/floats, leave the rest alone.
     */
    private function normaliseValues(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if (is_string($v) && $v !== '' && is_numeric($v)) {
                $out[$k] = str_contains($v, '.') ? (float) $v : (int) $v;
            } elseif (is_array($v)) {
                $out[$k] = array_values($v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
