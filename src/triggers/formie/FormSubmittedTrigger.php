<?php

namespace bymayo\points\triggers\formie;

use bymayo\points\triggers\BaseTrigger;
use bymayo\points\triggers\TriggerContext;
use verbb\formie\services\Submissions;
use yii\base\Event;

/**
 * Fires when a Formie form is submitted successfully by a logged-in user.
 *
 * Pair with the `Form is one of` condition to reward only specific forms,
 * or leave the condition off to reward any form submission.
 *
 * Anonymous submissions (visitor not logged in) are ignored - there's no
 * user to credit. Incomplete / multi-step intermediate saves are also
 * ignored; we only listen to `EVENT_AFTER_SUBMISSION` (the final one).
 */
class FormSubmittedTrigger extends BaseTrigger
{
    public function handle(): string { return 'formie.formSubmitted'; }
    public function label(): string { return 'Form submitted'; }
    public function group(): string { return 'Formie'; }
    public function subject(): string { return 'formieForm'; }
    public function actionLabel(): string { return 'Submitted'; }

    public function events(): array
    {
        return [[Submissions::class, Submissions::EVENT_AFTER_SUBMISSION]];
    }

    public function handleEvent(Event $event): ?TriggerContext
    {
        // Only act on successful submissions. Validation failures, spam,
        // and aborted multi-step flows all skip the award.
        if (!($event->success ?? false)) {
            return null;
        }

        $submission = $event->submission ?? null;
        if (!$submission) {
            return null;
        }

        // Skip spam if Formie flagged it.
        if (property_exists($submission, 'isSpam') && $submission->isSpam) {
            return null;
        }

        // Anonymous submissions (no logged-in user) can't earn points.
        $userId = $submission->userId ?: null;
        if (!$userId) {
            return null;
        }
        return new TriggerContext(userId: (int) $userId);
    }
}
