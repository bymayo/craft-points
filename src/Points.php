<?php

namespace bymayo\points;

use bymayo\points\elements\PointAward;
use bymayo\points\gql\types\EventType;
use bymayo\points\gql\types\LeaderboardRowType;
use bymayo\points\gql\types\LevelType;
use bymayo\points\gql\types\PointAwardType;
use bymayo\points\models\Settings;
use bymayo\points\services\Awards;
use bymayo\points\services\Events;
use bymayo\points\services\Levels;
use bymayo\points\services\Triggers;
use bymayo\points\variables\PointsVariable;
use bymayo\points\widgets\LatestAwardsWidget;
use bymayo\points\widgets\LeaderboardWidget;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
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
 * @property-read Events $events
 * @property-read Awards $awards
 * @property-read Levels $levels
 * @property-read Triggers $triggers
 * @author ByMayo <jason@bymayo.co.uk>
 * @copyright ByMayo
 * @license https://craftcms.github.io/license/ Craft License
 */
class Points extends Plugin
{
    public const EDITION_LITE = 'lite';
    public const EDITION_PRO = 'pro';

    public string $schemaVersion = '1.4.0';
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
                'events' => Events::class,
                'awards' => Awards::class,
                'levels' => Levels::class,
                'triggers' => Triggers::class,
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
        $item['label'] = $this->getSettings()->currencyNamePlural;

        $user = Craft::$app->getUser();
        $subnav = [];

        if ($user->checkPermission('points-manageAwards')) {
            $subnav['awards'] = ['label' => Craft::t('points', 'Awards'), 'url' => 'points/awards'];
        }
        if ($user->checkPermission('points-manageEvents')) {
            $subnav['events'] = ['label' => Craft::t('points', 'Events'), 'url' => 'points/events'];
        }
        if ($user->checkPermission('points-manageLevels')) {
            $subnav['levels'] = ['label' => Craft::t('points', 'Levels'), 'url' => 'points/levels'];
        }
        if ($user->checkPermission('points-manageAwards')) {
            $subnav['leaderboard'] = ['label' => Craft::t('points', 'Leaderboard'), 'url' => 'points/leaderboard'];
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

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
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

                $event->rules['points/events'] = 'points/events/index';
                $event->rules['points/events/new'] = 'points/events/edit';
                $event->rules['points/events/<eventId:\d+>'] = 'points/events/edit';

                $event->rules['points/levels'] = 'points/levels/index';
                $event->rules['points/levels/new'] = 'points/levels/edit';
                $event->rules['points/levels/<levelId:\d+>'] = 'points/levels/edit';

                $event->rules['points/leaderboard'] = 'points/leaderboard/index';
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
    }

    private function attachUserPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => $this->getSettings()->currencyNamePlural,
                    'permissions' => [
                        'points-manageEvents' => [
                            'label' => Craft::t('points', 'Manage events'),
                        ],
                        'points-manageAwards' => [
                            'label' => Craft::t('points', 'Manage awards'),
                        ],
                        'points-manageLevels' => [
                            'label' => Craft::t('points', 'Manage levels'),
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
                $event->types[] = EventType::class;
                $event->types[] = LevelType::class;
                $event->types[] = PointAwardType::class;
                $event->types[] = LeaderboardRowType::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries['pointsEvents'] = [
                    'type' => Type::listOf(EventType::getType()),
                    'args' => [],
                    'resolve' => fn() => self::getInstance()->events->getAllEvents(),
                    'description' => 'All Points events.',
                ];

                $event->queries['pointsEvent'] = [
                    'type' => EventType::getType(),
                    'args' => ['handle' => Type::nonNull(Type::string())],
                    'resolve' => fn($source, array $args) => self::getInstance()->events->getEventByHandle($args['handle']),
                    'description' => 'A single Points event by handle.',
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
                        'eventId' => Type::int(),
                        'limit' => Type::int(),
                        'offset' => Type::int(),
                    ],
                    'resolve' => function($source, array $args) {
                        $query = PointAward::find()
                            ->orderBy(['dateCreated' => SORT_DESC]);
                        if (isset($args['userId'])) {
                            $query->userId((int)$args['userId']);
                        }
                        if (isset($args['eventId'])) {
                            $query->eventId((int)$args['eventId']);
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
    }
}
