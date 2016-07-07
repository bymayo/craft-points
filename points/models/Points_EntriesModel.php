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

class Points_EntriesModel extends BaseModel
{
	/**
	 * 	Defined Model Attributes
	 *
	 * @return array
	 */
    protected function defineAttributes()
    {
        return array(
            'id' 			=> AttributeType::Number,
            'user' 			=> AttributeType::String,
            'eventHandle' 	=> AttributeType::String,
            'dateCreated' 	=> AttributeType::DateTime,
            'dateUpdated' 	=> AttributeType::DateTime
        );
        
    }

}