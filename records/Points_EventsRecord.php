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

class Points_EventsRecord extends BaseRecord
{
	
    /**
     * Get table name
     *
	 * @return string
     */
    public function getTableName()
    {
        return 'points_events';
    }

    /**
     * Define table columns
     *
	 * @return array
     */
   protected function defineAttributes()
    {
        return array(
            'event'     	=> array(AttributeType::String, 'required' => true),
            'eventHandle'   => array(AttributeType::String, 'required' => true),
            'points'    	=> array(AttributeType::Number, 'min' => null, 'max' => null, 'decimals' => 0, 'required' => true),
            'multiple'    	=> array(AttributeType::Bool, 'maxLength' => 1, 'default' => false)
        );
    }

}