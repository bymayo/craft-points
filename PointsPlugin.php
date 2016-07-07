<?php
/**
 * Points
 *
 * @author    Jason Mayo
 * @twitter   @madebymayo
 * @package   Points
 *
 */

namespace Craft;

class PointsPlugin extends BasePlugin
{
	
    public function init()
    {
	    
    }

    public function getName()
    {
         return Craft::t('Points');
    }

    public function getDescription()
    {
        return Craft::t('Assign points to a user and create leaderboards');
    }

    public function getDocumentationUrl()
    {
        return 'https://github.com/bymayo/points/blob/master/README.md';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/bymayo/points/master/releases.json';
    }

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    public function getDeveloper()
    {
        return 'Jason Mayo';
    }

    public function getDeveloperUrl()
    {
        return 'bymayo.co.uk';
    }

    public function hasCpSection()
    {
        return true;
    }
    
	public function registerCpRoutes()
	{
		
		return array(
			'points/events/add/(?P<rowId>\d+)' => 'points/events/add',
			'points/entries/add/(?P<rowId>\d+)' => 'points/entries/add'
		);
		
	}

    public function onBeforeInstall()
    {
    }

    public function onAfterInstall()
    {
    }

    public function onBeforeUninstall()
    {
    }

    public function onAfterUninstall()
    {
    }

}