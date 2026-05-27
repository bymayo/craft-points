<?php

/**
 * Example custom trigger + condition for the Points plugin.
 *
 * This file is a COPY-PASTE REFERENCE. It is not autoloaded by Composer
 * and references a fictional `yourvendor\comments` package — drop the two
 * classes below into your own plugin / module, adjust the namespace and
 * the `Comment` element reference, then register from your `init()`:
 *
 *     Points::getInstance()->triggers->register(new CommentPostedTrigger());
 *     // CommentLengthCondition is pulled in via conditions() — no extra call needed.
 *
 * See the "Extending the plugin" section of the README for the full walk-through.
 */

namespace mysite\points;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use craft\base\Element;
use craft\events\ModelEvent;
use craft\helpers\Cp;
use yii\base\Event;
use yourvendor\comments\elements\Comment;

/**
 * Awards points when a new Comment is posted.
 */
class CommentPostedTrigger extends BaseTrigger
{
    public function handle(): string  { return 'mysite.commentPosted'; }
    public function label(): string   { return 'Comment posted'; }
    public function group(): string   { return 'Comments'; }
    public function subject(): string { return 'comment'; }

    /**
     * One or more [Class, EVENT_CONST] pairs to subscribe to.
     */
    public function events(): array
    {
        return [[Comment::class, Element::EVENT_AFTER_SAVE]];
    }

    /**
     * Companion conditions — auto-registered when this trigger registers.
     */
    public function conditions(): array
    {
        return [new CommentLengthCondition()];
    }

    /**
     * Return a TriggerContext to award points, or null to skip this event.
     */
    public function handleEvent(Event $event): ?TriggerContext
    {
        /** @var ModelEvent $event */
        if (!($event->isNew ?? false)) {
            return null; // edits don't earn points, only new comments
        }

        /** @var Comment $comment */
        $comment = $event->sender;
        if (!$comment->authorId) {
            return null; // anonymous comments can't earn points
        }

        return new TriggerContext(userId: (int) $comment->authorId);
    }
}

/**
 * Optional "comment must be at least N characters" gate on the trigger.
 */
class CommentLengthCondition extends BaseConditionRule
{
    public function handle(): string { return 'mysite.commentLength'; }
    public function label(): string  { return 'Comment length'; }
    public function group(): string  { return 'Comments'; }

    /** Only show in the rule builder for triggers whose subject is 'comment'. */
    public function appliesToSubjects(): ?array { return ['comment']; }

    /** Render on one line in the rule builder (text input + suffix span). */
    public function isInline(): bool { return true; }

    /**
     * The picker UI in the rule builder. Points wraps the return value in
     * `<div class="cnd-variant" data-variant="…" hidden>`.
     */
    public function renderConfigUi(int $index, array $config): string
    {
        return Cp::textHtml([
            'name' => "conditions[{$index}][min]",
            'type' => 'number',
            'value' => (string) ($config['min'] ?? ''),
        ]) . ' <span class="cnd-suffix">characters or more</span>';
    }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $min = (int) ($config['min'] ?? 0);
        $comment = $ctx->triggerEvent?->sender ?? null;
        $length = $comment ? mb_strlen((string) $comment->body) : 0;
        return $length >= $min;
    }
}
