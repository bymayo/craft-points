<?php

namespace bymayo\points\triggers;

use bymayo\points\conditions\ConditionRuleInterface;
use yii\base\Event;

/**
 * A trigger is a self-contained, single-file integration point: it
 * advertises identity (handle/label/group), declares which Yii events
 * it wants to listen on, and converts each fired event into a
 * TriggerContext (or null to skip).
 *
 * Third-party plugins implement this interface and register an instance
 * via Points::getInstance()->triggers->register(new MyTrigger()), or
 * by appending to RegisterTriggersEvent::$triggers.
 */
interface TriggerInterface
{
    /** Unique trigger handle stored on the Rule record, e.g. 'entry.created'. */
    public function handle(): string;

    /** Human-readable label for the trigger select. */
    public function label(): string;

    /** Group label used to optgroup the trigger select (e.g. 'Entries', 'Users'). */
    public function group(): string;

    /** Subject this trigger fires on, e.g. 'entry', 'user', 'order'. */
    public function subject(): string;

    /** Human-readable subject label, e.g. 'Entry', 'User', 'Order'. */
    public function subjectLabel(): string;

    /** Action verb for the trigger, e.g. 'Created', 'Updated', 'Logged in'. */
    public function actionLabel(): string;

    /**
     * Yii events this trigger subscribes to.
     *
     * Return one or more `[ClassName, EVENT_CONST]` pairs. The Triggers
     * service attaches a single listener per (class, event) pair and
     * routes the event to every trigger that declared it.
     *
     * @return array<int, array{0: class-string, 1: string}>
     */
    public function events(): array;

    /**
     * Decide whether this event should award points and, if so, who to.
     *
     * Return a TriggerContext to dispatch through the rule pipeline, or
     * null to skip (validation failed, not a logged-in user, wrong sub-type
     * of event, etc.). This is the single per-trigger contract — everything
     * the dispatcher needs to know lives on the returned context.
     */
    public function handleEvent(Event $event): ?TriggerContext;

    /**
     * Return false to hide this trigger from the rule builder picker.
     *
     * For triggers that depend on optional configuration (e.g. the user
     * birthday trigger needing a configured field handle that exists on
     * the user field layout), return false until the dependency is met.
     */
    public function isAvailable(): bool;

    /**
     * Companion conditions that ship with this trigger.
     *
     * Use this to keep your trigger and its purpose-built "IF" conditions
     * in the same file: return one or more ConditionRuleInterface instances
     * and they'll be auto-registered with the Conditions service when the
     * trigger is registered.
     *
     * The conditions still need to declare their own `appliesToSubjects()`
     * so the rule builder UI filters them correctly.
     *
     * @return ConditionRuleInterface[]
     */
    public function conditions(): array;
}
