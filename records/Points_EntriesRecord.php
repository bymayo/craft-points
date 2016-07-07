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

class Points_EntriesRecord extends BaseRecord
{
	
    /**
     * Get table name
     *
	 * @return string
     */
    public function getTableName()
    {
        return 'points_entries';
    }

    /**
     * Define table columns
     *
	 * @return array
     */
   protected function defineAttributes()
    {
        return array(
            'user'     		=> array(AttributeType::String,  'required' => true),
            'eventHandle'   => array(AttributeType::String, 'required' => true)
        );
    }
    
}