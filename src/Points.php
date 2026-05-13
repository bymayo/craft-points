<?php

namespace bymayo\points;

use bymayo\points\elements\PointEntry;
use bymayo\points\models\Settings;
use bymayo\points\services\Entries;
use bymayo\points\services\Events;
use bymayo\points\services\Levels;
use bymayo\points\variables\PointsVariable;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Points plugin
 *
 * @method static Points getInstance()
 * @method Settings getSettings()
 * @property-read Events $events
 * @property-read Entries $entries
 * @property-read Levels $levels
 * @author ByMayo <jason@bymayo.co.uk>
 * @copyright ByMayo
 * @license https://craftcms.github.io/license/ Craft License
 */
class Points extends Plugin
{
    public string $schemaVersion = '1.1.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'events' => Events::class,
                'entries' => Entries::class,
                'levels' => Levels::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('points', 'Points');
        $item['subnav'] = [
            'entries' => ['label' => Craft::t('points', 'Entries'), 'url' => 'points/entries'],
            'events' => ['label' => Craft::t('points', 'Events'), 'url' => 'points/events'],
            'levels' => ['label' => Craft::t('points', 'Levels'), 'url' => 'points/levels'],
        ];
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
                $event->types[] = PointEntry::class;
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['points'] = 'points/entries/index';

                $event->rules['points/entries'] = 'points/entries/index';
                $event->rules['points/entries/new'] = 'points/entries/edit';
                $event->rules['points/entries/<entryId:\d+>'] = 'points/entries/edit';

                $event->rules['points/events'] = 'points/events/index';
                $event->rules['points/events/new'] = 'points/events/edit';
                $event->rules['points/events/<eventId:\d+>'] = 'points/events/edit';

                $event->rules['points/levels'] = 'points/levels/index';
                $event->rules['points/levels/new'] = 'points/levels/edit';
                $event->rules['points/levels/<levelId:\d+>'] = 'points/levels/edit';
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

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('points', 'Points'),
                    'permissions' => [
                        'points-manageEvents' => [
                            'label' => Craft::t('points', 'Manage events'),
                        ],
                        'points-manageEntries' => [
                            'label' => Craft::t('points', 'Manage entries'),
                        ],
                        'points-manageLevels' => [
                            'label' => Craft::t('points', 'Manage levels'),
                        ],
                    ],
                ];
            }
        );
    }
}
