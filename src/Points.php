<?php

namespace bymayo\points;

use bymayo\points\elements\PointAward;
use bymayo\points\gql\types\AddAwardResultType;
use bymayo\points\gql\types\LeaderboardRowType;
use bymayo\points\gql\types\LevelType;
use bymayo\points\gql\types\PointAwardType;
use bymayo\points\gql\types\RuleType;
use bymayo\points\models\Settings;
use bymayo\points\services\Awards;
use bymayo\points\services\Conditions;
use bymayo\points\services\Levels;
use bymayo\points\services\Limits;
use bymayo\points\services\OrderRedemptions;
use bymayo\points\services\Rewards;
use bymayo\points\services\Rules;
use bymayo\points\services\Triggers;
use bymayo\points\variables\PointsVariable;
use bymayo\points\widgets\LatestAwardsWidget;
use bymayo\points\widgets\LeaderboardWidget;
use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\elements\User;
use craft\events\DefineAttributeHtmlEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\Html;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\Gql;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use GraphQL\Type\Definition\Type;
use yii\base\Event;

/**
 * Points plugin
 *
 * @method static Points getInstance()
 * @method Settings getSettings()
 * @property-read Rules $rules
 * @property-read Awards $awards
 * @property-read Levels $levels
 * @property-read Triggers $triggers
 * @property-read Conditions $conditions
 * @property-read Limits $limits
 * @property-read Rewards $rewards
 * @property-read OrderRedemptions $orderRedemptions
 * @author ByMayo <jason@bymayo.co.uk>
 * @copyright ByMayo
 * @license https://craftcms.github.io/license/ Craft License
 */
class Points extends Plugin
{
    public const EDITION_LITE = 'lite';
    public const EDITION_PRO = 'pro';

    public string $schemaVersion = '1.9.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public static function config(): array
    {
        return [
            'components' => [
                'rules' => Rules::class,
                'awards' => Awards::class,
                'levels' => Levels::class,
                'triggers' => Triggers::class,
                'conditions' => Conditions::class,
                'limits' => Limits::class,
                'rewards' => Rewards::class,
                'orderRedemptions' => OrderRedemptions::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();

        // Eagerly instantiate the Triggers service so it can attach its Yii event listeners.
        $this->triggers;
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = $this->getSettings()->pluginName;

        $user = Craft::$app->getUser();
        $subnav = [];

        if ($user->checkPermission('points-viewAwards')) {
            $subnav['awards'] = ['label' => Craft::t('points', 'Awards'), 'url' => 'points/awards'];
        }
        if ($user->checkPermission('points-viewRules')) {
            $subnav['rules'] = ['label' => Craft::t('points', 'Rules'), 'url' => 'points/rules'];
        }
        if ($user->checkPermission('points-viewLevels')) {
            $subnav['levels'] = ['label' => Craft::t('points', 'Levels'), 'url' => 'points/levels'];
        }
        if ($user->checkPermission('points-viewLeaderboard')) {
            $subnav['leaderboard'] = ['label' => Craft::t('points', 'Leaderboard'), 'url' => 'points/leaderboard'];
        }
        if ($user->checkPermission('points-manageSettings')) {
            $subnav['settings'] = ['label' => Craft::t('points', 'Settings'), 'url' => 'settings/plugins/points'];
        }

        // Suppress the entire nav item if the user can't access any subpage.
        if (empty($subnav)) {
            return null;
        }

        $item['subnav'] = $subnav;
        return $item;
    }

    protected function cpNavIconPath(): ?string
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'icon-outline.svg';
        return file_exists($path) ? $path : parent::cpNavIconPath();
    }

    /**
     * Settings are stored in our own `{{%points_settings}}` row (NOT in project
     * config). This means admins can change branding & operational values
     * directly on a production environment without a deploy clobbering them,
     * and without the values syncing into `project.yaml` across environments.
     *
     * Devs can still override per-environment via `config/points.php` — values
     * there take precedence over the DB row, same shape as the Settings model.
     */
    protected function createSettingsModel(): ?Model
    {
        $model = Craft::createObject(Settings::class);

        // 1. Hydrate from our DB row (if it exists; missing during install).
        try {
            $row = (new Query())
                ->from('{{%points_settings}}')
                ->where(['id' => 1])
                ->one();
            if ($row && !empty($row['settings'])) {
                $data = Json::decodeIfJson($row['settings']);
                if (is_array($data)) {
                    $model->setAttributes($data, false);
                }
            }
        } catch (\Throwable $e) {
            // Table doesn't exist yet (pre-install / pre-migration). Defaults.
        }

        // 2. Overlay per-environment overrides from config/points.php.
        $fileConfig = Craft::$app->getConfig()->getConfigFromFile('points');
        if (!empty($fileConfig)) {
            $model->setAttributes($fileConfig, false);
        }

        return $model;
    }

    /**
     * Override Craft's default settings save (which routes through Project
     * Config) and write directly to `{{%points_settings}}` instead.
     */
    public function saveSettings(array $settings): bool
    {
        $model = $this->getSettings();
        $model->setAttributes($settings, false);
        if (!$model->validate()) {
            return false;
        }

        $db = Craft::$app->getDb();
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $payload = Json::encode($settings);

        $exists = (new Query())
            ->from('{{%points_settings}}')
            ->where(['id' => 1])
            ->exists();

        if ($exists) {
            $db->createCommand()
                ->update('{{%points_settings}}', [
                    'settings' => $payload,
                    'dateUpdated' => $now,
                ], ['id' => 1])
                ->execute();
        } else {
            $db->createCommand()
                ->insert('{{%points_settings}}', [
                    'id' => 1,
                    'settings' => $payload,
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ])
                ->execute();
        }

        return true;
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('points/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = PointAward::class;
            }
        );

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = LeaderboardWidget::class;
                $event->types[] = LatestAwardsWidget::class;
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['points'] = 'points/awards/index';

                $event->rules['points/awards'] = 'points/awards/index';
                $event->rules['points/awards/new'] = 'points/awards/edit';
                $event->rules['points/awards/<awardId:\d+>'] = 'points/awards/edit';
                $event->rules['POST points/awards/fire'] = 'points/awards/fire';
                $event->rules['POST points/awards/remove'] = 'points/awards/remove';
                $event->rules['points/awards/token'] = 'points/awards/token';

                $event->rules['points/rules'] = 'points/rules/index';
                $event->rules['points/rules/new'] = 'points/rules/edit';
                $event->rules['points/rules/<ruleId:\d+>'] = 'points/rules/edit';
                $event->rules['POST points/rules/add-row'] = 'points/rules/add-row';
                $event->rules['points/rules/table-data'] = 'points/rules/table-data';

                $event->rules['points/levels'] = 'points/levels/index';
                $event->rules['points/levels/new'] = 'points/levels/edit';
                $event->rules['points/levels/<levelId:\d+>'] = 'points/levels/edit';
                $event->rules['points/levels/table-data'] = 'points/levels/table-data';

                $event->rules['points/leaderboard'] = 'points/leaderboard/index';
                $event->rules['points/leaderboard/table-data'] = 'points/leaderboard/table-data';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('points', PointsVariable::class);
            }
        );

        $this->registerGraphQl();

        $this->attachUserPermissions();
        $this->attachOrderRedemptions();
    }

    /**
     * Pro + Commerce only: wire up the points-as-checkout-discount feature.
     */
    private function attachOrderRedemptions(): void
    {
        if (!$this->is(self::EDITION_PRO)) return;
        if (!Craft::$app->getPlugins()->isPluginEnabled('commerce')) return;

        // Register the adjuster that adds the negative line item to orders.
        Event::on(
            \craft\commerce\services\OrderAdjustments::class,
            \craft\commerce\services\OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = \bymayo\points\adjusters\PointsAdjuster::class;
            }
        );

        // Deduct points from the user's balance once the order is paid.
        Event::on(
            \craft\commerce\elements\Order::class,
            \craft\commerce\elements\Order::EVENT_AFTER_ORDER_PAID,
            function($event) {
                $this->orderRedemptions->processPaidOrder($event->sender);
            }
        );

        // Optionally restore points on successful refund (behaviour configurable).
        Event::on(
            \craft\commerce\services\Transactions::class,
            \craft\commerce\services\Transactions::EVENT_AFTER_SAVE_TRANSACTION,
            function($event) {
                $tx = $event->transaction ?? null;
                if (!$tx) return;
                if ($tx->type !== 'refund' || $tx->status !== 'success') return;
                $this->orderRedemptions->processRefund($tx);
            }
        );

        // Route for the apply / remove form actions.
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Action routing handles it; no site rules needed.
            }
        );
    }

    private function attachUserPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                // View → broad (read-only). Manage → nested (also create/edit/delete).
                // Granting Manage in the UI requires Granting View first; in code,
                // index/edit screens check view-* and write actions check manage-*.
                $event->permissions[] = [
                    'heading' => Craft::t('points', 'Points'),
                    'permissions' => [
                        'points-viewAwards' => [
                            'label' => Craft::t('points', 'View awards'),
                            'nested' => [
                                'points-createAwards' => ['label' => Craft::t('points', 'Create awards')],
                                'points-editAwards' => ['label' => Craft::t('points', 'Edit awards')],
                                'points-deleteAwards' => ['label' => Craft::t('points', 'Delete awards')],
                            ],
                        ],
                        'points-viewRules' => [
                            'label' => Craft::t('points', 'View rules'),
                            'nested' => [
                                'points-createRules' => ['label' => Craft::t('points', 'Create rules')],
                                'points-editRules' => ['label' => Craft::t('points', 'Edit rules')],
                                'points-deleteRules' => ['label' => Craft::t('points', 'Delete rules')],
                            ],
                        ],
                        'points-viewLevels' => [
                            'label' => Craft::t('points', 'View levels'),
                            'nested' => [
                                'points-createLevels' => ['label' => Craft::t('points', 'Create levels')],
                                'points-editLevels' => ['label' => Craft::t('points', 'Edit levels')],
                                'points-deleteLevels' => ['label' => Craft::t('points', 'Delete levels')],
                            ],
                        ],
                        'points-viewLeaderboard' => [
                            'label' => Craft::t('points', 'View leaderboard'),
                        ],
                        'points-manageSettings' => [
                            'label' => Craft::t('points', 'Manage settings'),
                        ],
                    ],
                ];
            }
        );
    }

    private function registerGraphQl(): void
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                $event->types[] = RuleType::class;
                $event->types[] = LevelType::class;
                $event->types[] = PointAwardType::class;
                $event->types[] = LeaderboardRowType::class;
                $event->types[] = AddAwardResultType::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_MUTATIONS,
            function(RegisterGqlMutationsEvent $event) {
                $event->mutations['pointsAddAward'] = [
                    'type' => AddAwardResultType::getType(),
                    'args' => [
                        'ruleHandle' => Type::nonNull(Type::string()),
                    ],
                    'description' => 'Fire a Manual rule and award points to the currently-authenticated user.',
                    'resolve' => function($source, array $args) {
                        $user = Craft::$app->getUser()->getIdentity();
                        if (!$user) {
                            return ['success' => false, 'error' => 'Not authenticated.'];
                        }

                        $handle = (string) $args['ruleHandle'];
                        $rule = self::getInstance()->rules->getRuleByHandle($handle);

                        if (!$rule || !$rule->enabled || $rule->handle === '__redemption') {
                            return ['success' => false, 'error' => 'Rule not available.'];
                        }

                        // Same security boundary as the REST endpoint:
                        // only Manual rules (no trigger) can be fired this way.
                        if ($rule->trigger) {
                            return ['success' => false, 'error' => 'Automatic rules cannot be fired via this mutation.'];
                        }

                        $now = (new \DateTime())->format('Y-m-d H:i:s');
                        if ($rule->activeFrom && $now < $rule->activeFrom) {
                            return ['success' => false, 'error' => 'Rule is not yet active.'];
                        }
                        if ($rule->activeTo && $now > $rule->activeTo) {
                            return ['success' => false, 'error' => 'Rule is no longer active.'];
                        }

                        $award = self::getInstance()->awards->addAward($user->id, $handle);
                        if (!$award) {
                            return ['success' => false, 'error' => 'Could not award points (limit reached or rule rejected).'];
                        }

                        $settings = self::getInstance()->getSettings();
                        return [
                            'success' => true,
                            'points' => $award->pointsSnapshot,
                            'currency' => $settings->currencyNamePlural,
                            'awardId' => $award->id,
                        ];
                    },
                ];
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries['pointsRules'] = [
                    'type' => Type::listOf(RuleType::getType()),
                    'args' => [],
                    'resolve' => fn() => self::getInstance()->rules->getAllRules(),
                    'description' => 'All Points rules.',
                ];

                $event->queries['pointsRule'] = [
                    'type' => RuleType::getType(),
                    'args' => ['handle' => Type::nonNull(Type::string())],
                    'resolve' => fn($source, array $args) => self::getInstance()->rules->getRuleByHandle($args['handle']),
                    'description' => 'A single Points rule by handle.',
                ];

                $event->queries['pointsLevels'] = [
                    'type' => Type::listOf(LevelType::getType()),
                    'args' => [],
                    'resolve' => fn() => self::getInstance()->levels->getAllLevels(),
                    'description' => 'All Points levels, ordered by threshold ascending.',
                ];

                $event->queries['pointsLevelForUser'] = [
                    'type' => LevelType::getType(),
                    'args' => ['userId' => Type::nonNull(Type::int())],
                    'resolve' => fn($source, array $args) => self::getInstance()->levels->levelForUser((int)$args['userId']),
                    'description' => "The user's current level.",
                ];

                $event->queries['pointsAwards'] = [
                    'type' => Type::listOf(PointAwardType::getType()),
                    'args' => [
                        'userId' => Type::int(),
                        'ruleId' => Type::int(),
                        'limit' => Type::int(),
                        'offset' => Type::int(),
                    ],
                    'resolve' => function($source, array $args) {
                        $query = PointAward::find()
                            ->orderBy(['dateCreated' => SORT_DESC]);
                        if (isset($args['userId'])) {
                            $query->userId((int)$args['userId']);
                        }
                        if (isset($args['ruleId'])) {
                            $query->ruleId((int)$args['ruleId']);
                        }
                        if (isset($args['limit'])) {
                            $query->limit((int)$args['limit']);
                        }
                        if (isset($args['offset'])) {
                            $query->offset((int)$args['offset']);
                        }
                        return $query->all();
                    },
                    'description' => 'Query Points awards.',
                ];

                $event->queries['pointsSumForUser'] = [
                    'type' => Type::int(),
                    'args' => ['userId' => Type::nonNull(Type::int())],
                    'resolve' => fn($source, array $args) => self::getInstance()->awards->sumForUser((int)$args['userId']),
                    'description' => "The user's total points.",
                ];

                $event->queries['pointsCountForUser'] = [
                    'type' => Type::int(),
                    'args' => ['userId' => Type::nonNull(Type::int())],
                    'resolve' => fn($source, array $args) => self::getInstance()->awards->countForUser((int)$args['userId']),
                    'description' => "Count of awards for the user.",
                ];

                $event->queries['pointsLeaderboard'] = [
                    'type' => Type::listOf(LeaderboardRowType::getType()),
                    'args' => [
                        'limit' => Type::int(),
                        'offset' => Type::int(),
                    ],
                    'resolve' => function($source, array $args) {
                        return self::getInstance()->awards->leaderboard(
                            (int)($args['limit'] ?? 10),
                            (int)($args['offset'] ?? 0)
                        );
                    },
                    'description' => 'Top users by total points.',
                ];
            }
        );

        // Add optional columns to Craft's built-in Users element index so admins
        // can see each user's running balance, current level, available spend
        // value, and lifetime redemptions. All opt-in via column settings.
        //
        // Currency total and Level are Lite features. Available Spend and
        // Redeemed are Commerce concepts and gate behind Pro.
        Event::on(
            User::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function(RegisterElementTableAttributesEvent $event) {
                $plugin = self::getInstance();
                $settings = $plugin->getSettings();

                $event->tableAttributes['pointsTotal'] = [
                    'label' => $settings->currencyNamePlural,
                ];
                $event->tableAttributes['pointsLevel'] = [
                    'label' => Craft::t('points', 'Level'),
                ];
                if ($plugin->is(self::EDITION_PRO)) {
                    $event->tableAttributes['pointsSpend'] = [
                        'label' => Craft::t('points', 'Available Spend'),
                    ];
                    $event->tableAttributes['pointsRedeemed'] = [
                        'label' => Craft::t('points', 'Redeemed'),
                    ];
                }
            }
        );

        Event::on(
            User::class,
            Element::EVENT_DEFINE_ATTRIBUTE_HTML,
            function(DefineAttributeHtmlEvent $event) {
                if (!in_array($event->attribute, ['pointsTotal', 'pointsLevel', 'pointsSpend', 'pointsRedeemed'], true)) {
                    return;
                }

                /** @var User $user */
                $user = $event->sender;
                if (!$user->id) {
                    $event->html = '';
                    return;
                }

                $plugin = self::getInstance();
                $settings = $plugin->getSettings();
                $points = $plugin->awards->sumForUser($user->id);

                switch ($event->attribute) {
                    case 'pointsTotal':
                        $event->html = Craft::$app->getFormatter()->asInteger($points);
                        break;

                    case 'pointsLevel':
                        $level = $plugin->levels->levelForPoints($points);
                        if (!$level) {
                            $event->html = '<span class="light">—</span>';
                            break;
                        }
                        $colour = method_exists($level, 'getColourHex')
                            ? ($level->getColourHex() ?: '#808080')
                            : '#808080';
                        $event->html = sprintf(
                            '<span style="display:inline-flex;align-items:center;gap:6px;">'
                            . '<span style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;"></span>'
                            . '%s'
                            . '</span>',
                            Html::encode($colour),
                            Html::encode($level->name),
                        );
                        break;

                    case 'pointsSpend':
                        $rate = max(1, (int)$settings->pointsPerCurrencyUnit);
                        $value = $points / $rate;
                        $event->html = Html::encode($settings->currencySymbol) . number_format($value, 2);
                        break;

                    case 'pointsRedeemed':
                        $redeemed = $plugin->awards->getRedeemedPointsForUser($user->id);
                        $rate = max(1, (int)$settings->pointsPerCurrencyUnit);
                        $value = $redeemed / $rate;
                        $event->html = Html::encode($settings->currencySymbol) . number_format($value, 2);
                        break;
                }
            }
        );
    }
}
