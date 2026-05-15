<?php

namespace bymayo\points\triggers\formie;

use bymayo\points\triggers\BaseTrigger;
use verbb\formie\services\Submissions;

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
    public static function handle(): string { return 'formie.formSubmitted'; }
    public static function label(): string { return 'Form submitted'; }
    public static function group(): string { return 'Formie'; }
    public static function subject(): string { return 'formieForm'; }
    public static function actionLabel(): string { return 'Submitted'; }

    public static function eventClass(): string { return Submissions::class; }
    public static function eventName(): string { return Submissions::EVENT_AFTER_SUBMISSION; }

    public static function appliesToEvent($event): bool
    {
        // Only act on successful submissions. Validation failures, spam,
        // and aborted multi-step flows all skip the award.
        if (!($event->success ?? false)) {
            return false;
        }

        $submission = $event->submission ?? null;
        if (!$submission) {
            return false;
        }

        // Skip spam if Formie flagged it.
        if (property_exists($submission, 'isSpam') && $submission->isSpam) {
            return false;
        }

        return true;
    }

    public static function getUserIdFromEvent($event): ?int
    {
        $submission = $event->submission ?? null;
        // Anonymous submissions (no logged-in user) can't earn points.
        return $submission?->userId ?: null;
    }
}
