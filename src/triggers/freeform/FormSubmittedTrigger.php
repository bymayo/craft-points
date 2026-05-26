<?php

namespace bymayo\points\triggers\freeform;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use Solspace\Freeform\Services\SubmissionsService;
use yii\base\Event;

/**
 * Fires when a Freeform form is submitted successfully by a
 * logged-in user. Mirrors the Formie trigger - same shape, different
 * underlying plugin.
 *
 * Pair with the `Form is` condition (Freeform) to scope to specific
 * forms, or leave the condition off to reward any form submission.
 *
 * Anonymous submissions and spam are ignored.
 */
class FormSubmittedTrigger extends BaseTrigger
{
    public function handle(): string { return 'freeform.formSubmitted'; }
    public function label(): string { return 'Form submitted'; }
    public function group(): string { return 'Freeform'; }
    public function subject(): string { return 'freeformForm'; }
    public function actionLabel(): string { return 'Submitted'; }

    public function events(): array
    {
        return [[SubmissionsService::class, SubmissionsService::EVENT_AFTER_SUBMIT]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        $submission = $event->submission ?? null;
        if (!$submission) {
            return null;
        }

        if (property_exists($submission, 'isSpam') && $submission->isSpam) {
            return null;
        }

        $userId = $submission->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
