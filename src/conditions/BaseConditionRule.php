<?php

namespace bymayo\points\conditions;

use Craft;
use craft\web\View;

abstract class BaseConditionRule implements ConditionRuleInterface
{
    public function group(): string
    {
        return 'General';
    }

    public function appliesToSubjects(): ?array
    {
        return null; // applies to all subjects, including Manual
    }

    public function renderConfigUi(int $index, array $config): string
    {
        return '';
    }

    public function isInline(): bool
    {
        return false;
    }

    /**
     * Render one of Craft's CP form-helper templates (e.g.
     * `_includes/forms/checkboxSelect.twig`) and return its HTML.
     *
     * Useful from `renderConfigUi()` when no equivalent `Cp::*Html()` method
     * is exposed.
     */
    protected function renderFormTemplate(string $template, array $config): string
    {
        return Craft::$app->getView()->renderTemplate(
            $template,
            $config,
            View::TEMPLATE_MODE_CP,
        );
    }
}
