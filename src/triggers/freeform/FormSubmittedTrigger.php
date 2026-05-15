<?php

namespace bymayo\points\triggers\freeform;

use bymayo\points\triggers\BaseTrigger;
use Solspace\Freeform\Services\SubmissionsService;

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
    public static function handle(): string { return 'freeform.formSubmitted'; }
    public static function label(): string { return 'Form submitted'; }
    public static function group(): string { return 'Freeform'; }
    public static function subject(): string { return 'freeformForm'; }
    public static function actionLabel(): string { return 'Submitted'; }

    public static function eventClass(): string { return SubmissionsService::class; }
    public static function eventName(): string { return SubmissionsService::EVENT_AFTER_SUBMIT; }

    public static function appliesToEvent($event): bool
    {
        $submission = $event->submission ?? null;
        if (!$submission) {
            return false;
        }

        if (property_exists($submission, 'isSpam') && $submission->isSpam) {
            return false;
        }

        return true;
    }

    public static function getUserIdFromEvent($event): ?int
    {
        $submission = $event->submission ?? null;
        return $submission?->userId ?: null;
    }
}
