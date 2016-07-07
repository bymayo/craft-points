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
class PointsWidget extends BaseWidget
{
    /**
     * @return mixed
     */
    public function getName()
    {
        return Craft::t('Points');
    }
    /**
     * @return mixed
     */
    public function getBodyHtml()
    {
        craft()->templates->includeCssResource('points/css/style.css');
        craft()->templates->includeJsResource('points/js/script.js');
        return craft()->templates->render('points/widget');
    }
}