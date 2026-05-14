<?php

namespace bymayo\points\triggers;

interface TriggerInterface
{
    /** Unique trigger handle stored on the Event record, e.g. 'entry.created'. */
    public static function handle(): string;

    /** Human-readable label for the trigger select. */
    public static function label(): string;

    /** Group label for optgroup'ing the trigger select (e.g. 'Entries', 'Users'). */
    public static function group(): string;

    /** The element/subject this trigger fires on, e.g. 'entry', 'user', 'order'. */
    public static function subject(): string;

    /** Human-readable subject label, e.g. 'Entry', 'User', 'Order'. */
    public static function subjectLabel(): string;

    /** Action verb for the trigger, e.g. 'Created', 'Updated', 'Logged in'. */
    public static function actionLabel(): string;

    /** The Yii/Craft event source class to listen on. */
    public static function eventClass(): string;

    /** The event name constant to listen for. */
    public static function eventName(): string;

    /**
     * Return true if the underlying event matches this trigger's intent
     * (e.g. an Entry save event with $isNew === true for an "Entry created" trigger).
     */
    public static function appliesToEvent($event): bool;

    /** Resolve the user ID that should receive points for this event. */
    public static function getUserIdFromEvent($event): ?int;

    /**
     * Return the monetary amount for this event (e.g. order total), if applicable.
     *
     * Used by percentage-of-amount point awards. Triggers that don't have an
     * amount context (most non-Commerce ones) should return null.
     */
    public static function getAmountForEvent($event): ?float;

    /**
     * Return false to hide this trigger from the rule builder picker.
     *
     * For triggers that depend on optional configuration (e.g. the user
     * birthday trigger needing a configured field handle that exists on
     * the user field layout), return false until the dependency is met.
     */
    public static function isAvailable(): bool;

    /**
     * Return the Commerce order ID this event was about, if applicable.
     * Used to stamp the resulting award with a back-reference to the order
     * so the Awards index can link back. Non-Commerce triggers return null.
     */
    public static function getOrderIdFromEvent($event): ?int;
}
