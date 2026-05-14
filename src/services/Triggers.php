<?php

namespace bymayo\points\services;

use bymayo\points\conditions\RuleEvaluationContext;
use bymayo\points\events\RegisterTriggersEvent;
use bymayo\points\Points;
use bymayo\points\triggers\AssetCreatedTrigger;
use bymayo\points\triggers\commerce\FirstOrderTrigger;
use bymayo\points\triggers\commerce\OrderCompletedTrigger;
use bymayo\points\triggers\commerce\OrderPaidTrigger;
use bymayo\points\triggers\commerce\OrderRefundedTrigger;
use bymayo\points\triggers\commerce\SubscriptionCancelledTrigger;
use bymayo\points\triggers\commerce\SubscriptionCreatedTrigger;
use bymayo\points\triggers\commerce\SubscriptionPlanChangedTrigger;
use bymayo\points\triggers\commerce\SubscriptionRenewedTrigger;
use bymayo\points\triggers\EntryCreatedTrigger;
use bymayo\points\triggers\EntryUpdatedTrigger;
use bymayo\points\triggers\TriggerInterface;
use bymayo\points\triggers\UserAnniversaryTrigger;
use bymayo\points\triggers\UserBirthdayTrigger;
use bymayo\points\triggers\UserLoggedInTrigger;
use bymayo\points\triggers\UserRegisteredTrigger;
use Craft;
use yii\base\Component;
use yii\base\Event;

class Triggers extends Component
{
    public const EVENT_REGISTER_TRIGGERS = 'registerTriggers';

    /** @var string[] Trigger class names, after the register event has fired. */
    private array $_triggers = [];

    /** @var array<string, string> Map of trigger handle → class. */
    private array $_byHandle = [];

    public function init(): void
    {
        parent::init();
        $this->registerTriggers();
        $this->attachListeners();
    }

    /** @return string[] */
    public function getAllTriggers(): array
    {
        return $this->_triggers;
    }

    public function getTriggerClassByHandle(string $handle): ?string
    {
        return $this->_byHandle[$handle] ?? null;
    }

    public function getSelectOptions(): array
    {
        $options = [
            ['label' => Craft::t('points', 'Manual'), 'value' => ''],
        ];

        $byGroup = [];
        foreach ($this->_triggers as $class) {
            // Skip triggers that aren't ready to fire (e.g. UserBirthdayTrigger
            // without a configured field handle). They'd never be reachable
            // anyway — hiding them keeps the picker honest.
            if (!$class::isAvailable()) {
                continue;
            }
            $byGroup[$class::group()][] = $class;
        }

        foreach ($byGroup as $group => $classes) {
            $options[] = ['optgroup' => $group];
            foreach ($classes as $class) {
                $options[] = [
                    'label' => $class::label(),
                    'value' => $class::handle(),
                ];
            }
        }

        return $options;
    }

    private function registerTriggers(): void
    {
        $defaults = [
            EntryCreatedTrigger::class,
            EntryUpdatedTrigger::class,
            AssetCreatedTrigger::class,
            UserRegisteredTrigger::class,
            UserLoggedInTrigger::class,
            UserBirthdayTrigger::class,
            UserAnniversaryTrigger::class,
        ];

        // Commerce triggers are Pro-only and require Commerce to be installed.
        $isPro = Points::getInstance()->is(Points::EDITION_PRO);
        if ($isPro && Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $defaults[] = OrderCompletedTrigger::class;
            $defaults[] = OrderPaidTrigger::class;
            $defaults[] = OrderRefundedTrigger::class;
            $defaults[] = FirstOrderTrigger::class;
            if (class_exists('craft\\commerce\\elements\\Subscription')) {
                $defaults[] = SubscriptionCreatedTrigger::class;
                $defaults[] = SubscriptionRenewedTrigger::class;
                $defaults[] = SubscriptionCancelledTrigger::class;
                $defaults[] = SubscriptionPlanChangedTrigger::class;
            }
        }

        $event = new RegisterTriggersEvent(['triggers' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_TRIGGERS, $event);

        $this->_triggers = array_values(array_unique($event->triggers));

        foreach ($this->_triggers as $class) {
            if (!is_subclass_of($class, TriggerInterface::class)) {
                continue;
            }
            $this->_byHandle[$class::handle()] = $class;
        }
    }

    private function attachListeners(): void
    {
        $grouped = [];
        foreach ($this->_triggers as $class) {
            $key = $class::eventClass() . '::' . $class::eventName();
            $grouped[$key][] = $class;
        }

        foreach ($grouped as $triggerClasses) {
            $first = $triggerClasses[0];
            Event::on(
                $first::eventClass(),
                $first::eventName(),
                function($yiiEvent) use ($triggerClasses) {
                    foreach ($triggerClasses as $triggerClass) {
                        $this->dispatch($triggerClass, $yiiEvent);
                    }
                }
            );
        }
    }

    /**
     * Evaluate every rule wired to this trigger.
     *
     * Pipeline: trigger filters → enabled/active dates → conditions → limits → reward → addAward.
     */
    private function dispatch(string $triggerClass, $yiiEvent): void
    {
        if (!$triggerClass::appliesToEvent($yiiEvent)) {
            return;
        }

        $rules = Points::getInstance()->rules->getRulesByTrigger($triggerClass::handle());
        if (empty($rules)) {
            return;
        }

        $userId = $triggerClass::getUserIdFromEvent($yiiEvent);
        if (!$userId) {
            return;
        }

        $amount = $triggerClass::getAmountForEvent($yiiEvent);
        $orderId = $triggerClass::getOrderIdFromEvent($yiiEvent);

        $points = Points::getInstance();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        foreach ($rules as $rule) {
            // Enabled + active date range
            if (!$rule->enabled) continue;
            if ($rule->activeFrom && $now < $rule->activeFrom) continue;
            if ($rule->activeTo && $now > $rule->activeTo) continue;

            $ctx = new RuleEvaluationContext([
                'userId' => $userId,
                'rule' => $rule,
                'triggerHandle' => $triggerClass::handle(),
                'triggerEvent' => $yiiEvent,
                'amount' => $amount,
            ]);

            // Conditions
            if (!empty($rule->conditions) && !$points->conditions->evaluateAll($rule->conditions, $ctx)) {
                continue;
            }

            // Limits
            if (!empty($rule->limits) && !$points->limits->checkAll($rule->limits, $ctx)) {
                continue;
            }

            // Reward (negative values are valid — used for deductions).
            $awardPoints = $points->rewards->calculate($rule->reward, $ctx);
            if ($awardPoints === 0) {
                continue;
            }

            $points->awards->addAward($userId, $rule->handle, $awardPoints, $orderId);
        }
    }
}
