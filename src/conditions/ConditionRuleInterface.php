<?php

namespace bymayo\points\conditions;

/**
 * A condition is a self-contained "IF" clause that gates whether a rule
 * fires for a given trigger event.
 *
 * Third-party plugins can register conditions either standalone via
 * Points::getInstance()->conditions->register(new MyCondition()), or
 * alongside a custom trigger by returning them from
 * TriggerInterface::conditions().
 */
interface ConditionRuleInterface
{
    /** Unique handle stored in the rule's `conditions` JSON, e.g. 'section'. */
    public function handle(): string;

    /** Human-readable label for the condition picker. */
    public function label(): string;

    /** Group label for organising in the picker (e.g. 'Element', 'Commerce'). */
    public function group(): string;

    /**
     * Trigger subject handles this condition applies to (e.g. ['entry'], ['order']).
     * Return null or empty to apply to all subjects (and to Manual rules).
     *
     * @return string[]|null
     */
    public function appliesToSubjects(): ?array;

    /**
     * Evaluate this condition against the trigger context.
     *
     * @param array $config The condition's stored config (e.g. ['ids' => [1, 2]]).
     */
    public function evaluate(array $config, RuleEvaluationContext $ctx): bool;

    /**
     * Render the inner HTML for this condition's value picker in the rule
     * builder. Points wraps the result in
     * `<div class="cnd-variant" data-variant="{handle}" hidden>...</div>`
     * automatically.
     *
     * Return `''` for conditions that take no further configuration.
     *
     * @param int   $index  Row index — use to build input names, e.g.
     *                      `"conditions[{$index}][ids]"`.
     * @param array $config Saved config when this condition is the row's
     *                      active type; `[]` when not. Drives `value=""`
     *                      defaults so non-active variants don't carry
     *                      stale state into the form.
     */
    public function renderConfigUi(int $index, array $config): string;

    /**
     * Render the variant as a single-line layout (adds `cnd-inline` to the
     * wrapper). Defaults to `false` (block layout).
     */
    public function isInline(): bool;
}
