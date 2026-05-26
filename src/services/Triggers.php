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
use bymayo\points\triggers\formie\FormSubmittedTrigger as FormieFormSubmittedTrigger;
use bymayo\points\triggers\freeform\FormSubmittedTrigger as FreeformFormSubmittedTrigger;
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

    /** @var TriggerInterface[] All registered trigger instances, post-init. */
    private array $_triggers = [];

    /** @var array<string, TriggerInterface> Handle → trigger. First registration wins. */
    private array $_byHandle = [];

    /** @var array<string, TriggerInterface[]> 'Class::EVENT' → triggers listening for it. */
    private array $_byEventKey = [];

    /** @var array<string, true> 'Class::EVENT' keys we've already attached a Yii listener for. */
    private array $_attached = [];

    /** @var TriggerInterface[] Triggers registered via register() before init() ran. */
    private array $_pending = [];

    private bool $_initialized = false;

    public function init(): void
    {
        parent::init();
        $this->registerTriggers();
        $this->_initialized = true;
        $this->attachListenersFor($this->_triggers);
    }

    /** @return TriggerInterface[] */
    public function getAllTriggers(): array
    {
        return $this->_triggers;
    }

    public function getTriggerByHandle(string $handle): ?TriggerInterface
    {
        return $this->_byHandle[$handle] ?? null;
    }

    /**
     * Sugar API for third-party plugins. Call from your plugin's init():
     *
     *     Points::getInstance()->triggers->register(new MyTrigger());
     *
     * Safe to call before this service initialises — registrations are
     * buffered and merged with the built-ins during init(). After init,
     * the trigger is added immediately and its Yii event listeners are
     * attached on the spot.
     */
    public function register(TriggerInterface $trigger): void
    {
        if (!$this->_initialized) {
            $this->_pending[] = $trigger;
            return;
        }

        if ($this->addTrigger($trigger)) {
            $this->attachListenersFor([$trigger]);
        }
    }

    public function getSelectOptions(): array
    {
        $options = [
            ['label' => Craft::t('points', 'Manual'), 'value' => ''],
        ];

        $byGroup = [];
        foreach ($this->_triggers as $trigger) {
            // Skip triggers that aren't ready to fire (e.g. UserBirthdayTrigger
            // without a configured field handle). They'd never be reachable
            // anyway — hiding them keeps the picker honest.
            if (!$trigger->isAvailable()) {
                continue;
            }
            $byGroup[$trigger->group()][] = $trigger;
        }

        foreach ($byGroup as $group => $triggers) {
            $options[] = ['optgroup' => $group];
            foreach ($triggers as $trigger) {
                $options[] = [
                    'label' => $trigger->label(),
                    'value' => $trigger->handle(),
                ];
            }
        }

        return $options;
    }

    private function registerTriggers(): void
    {
        $defaults = [
            new EntryCreatedTrigger(),
            new EntryUpdatedTrigger(),
            new AssetCreatedTrigger(),
            new UserRegisteredTrigger(),
            new UserLoggedInTrigger(),
            new UserBirthdayTrigger(),
            new UserAnniversaryTrigger(),
        ];

        // Form-plugin triggers — Lite, only register when the underlying plugin
        // is installed (we reference their event classes).
        if (Craft::$app->getPlugins()->isPluginEnabled('formie')) {
            $defaults[] = new FormieFormSubmittedTrigger();
        }
        if (Craft::$app->getPlugins()->isPluginEnabled('freeform')) {
            $defaults[] = new FreeformFormSubmittedTrigger();
        }

        // Commerce triggers are Pro-only and require Commerce to be installed.
        $isPro = Points::getInstance()->is(Points::EDITION_PRO);
        if ($isPro && Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
            $defaults[] = new OrderCompletedTrigger();
            $defaults[] = new OrderPaidTrigger();
            $defaults[] = new OrderRefundedTrigger();
            $defaults[] = new FirstOrderTrigger();
            if (class_exists('craft\\commerce\\elements\\Subscription')) {
                $defaults[] = new SubscriptionCreatedTrigger();
                $defaults[] = new SubscriptionRenewedTrigger();
                $defaults[] = new SubscriptionCancelledTrigger();
                $defaults[] = new SubscriptionPlanChangedTrigger();
            }
        }

        $event = new RegisterTriggersEvent(['triggers' => $defaults]);
        $this->trigger(self::EVENT_REGISTER_TRIGGERS, $event);

        foreach ($event->triggers as $trigger) {
            if ($trigger instanceof TriggerInterface) {
                $this->addTrigger($trigger);
            }
        }
        foreach ($this->_pending as $trigger) {
            $this->addTrigger($trigger);
        }
        $this->_pending = [];
    }

    /** Returns true if newly added, false if a trigger with this handle was already registered. */
    private function addTrigger(TriggerInterface $trigger): bool
    {
        $handle = $trigger->handle();
        if (isset($this->_byHandle[$handle])) {
            return false;
        }
        $this->_byHandle[$handle] = $trigger;
        $this->_triggers[] = $trigger;
        foreach ($trigger->events() as $pair) {
            [$class, $eventName] = $pair;
            $this->_byEventKey[$class . '::' . $eventName][] = $trigger;
        }

        // Auto-register any companion conditions the trigger ships with.
        // Lets a custom integration keep its WHEN and its IFs in one file.
        foreach ($trigger->conditions() as $condition) {
            Points::getInstance()->conditions->register($condition);
        }

        return true;
    }

    /**
     * Attach one Yii listener per unique (class, event) pair across the given triggers.
     * The closure consults the live $_byEventKey map, so triggers registered later
     * for the same key are picked up without re-attaching anything.
     *
     * @param TriggerInterface[] $triggers
     */
    private function attachListenersFor(array $triggers): void
    {
        foreach ($triggers as $trigger) {
            foreach ($trigger->events() as $pair) {
                [$class, $eventName] = $pair;
                $key = $class . '::' . $eventName;
                if (isset($this->_attached[$key])) {
                    continue;
                }
                $this->_attached[$key] = true;
                Event::on($class, $eventName, function($yiiEvent) use ($key) {
                    foreach ($this->_byEventKey[$key] ?? [] as $listener) {
                        $this->dispatch($listener, $yiiEvent);
                    }
                });
            }
        }
    }

    /**
     * Evaluate every rule wired to this trigger.
     *
     * Pipeline: trigger.handleEvent → enabled/active dates → conditions → limits → reward → addAward.
     */
    private function dispatch(TriggerInterface $trigger, $yiiEvent): void
    {
        $context = $trigger->handleEvent($yiiEvent);
        if ($context === null) {
            return;
        }

        $rules = Points::getInstance()->rules->getRulesByTrigger($trigger->handle());
        if (empty($rules)) {
            return;
        }

        $points = Points::getInstance();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        foreach ($rules as $rule) {
            // Enabled + active date range
            if (!$rule->enabled) continue;
            if ($rule->activeFrom && $now < $rule->activeFrom) continue;
            if ($rule->activeTo && $now > $rule->activeTo) continue;

            $ctx = new RuleEvaluationContext([
                'userId' => $context->userId,
                'rule' => $rule,
                'triggerHandle' => $trigger->handle(),
                'triggerEvent' => $yiiEvent,
                'amount' => $context->amount,
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

            $points->awards->addAward($context->userId, $rule->handle, $awardPoints, $context->orderId);
        }
    }
}
