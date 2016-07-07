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

class Points_EventsModel extends BaseModel
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
            'event' 		=> AttributeType::String,
            'eventHandle'	=> AttributeType::String,
            'points' 		=> AttributeType::Number,
            'multiple'     	=> AttributeType::Bool,
            'dateCreated' 	=> AttributeType::DateTime,
            'dateUpdated' 	=> AttributeType::DateTime
        );
        
    }

}