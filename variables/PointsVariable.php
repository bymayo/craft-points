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

class PointsVariable
{
	
    /**
     * Entries - List
     *
	 * @return EntriesModel
     */
	public function entries()
	{
		return craft()->points->entries();	
	}
	
    /**
     * Events - List
	 *
	 * @return EventsModel
     */	
	public function events()
	{
		return craft()->points->events();	
	}
	
    /**
     * User
     *
	 * @param int $id
	 *
	 * @return array
     */
	public function user($userId) 
	{
		return craft()->points->user($userId);
	}
	
    /**
     * Event - Options
	 *
	 * @return array
     */
	public function eventOptions() 
	{
		return craft()->points->eventOptions();
	}
	
    /**
     * Event
	 *
	 * @param string $handle
	 *
	 * @return EventsModel
     */
	public function event($handle) 
	{
			
		return craft()->points->event($handle);
	}
	
    /**
     * Event - By ID
	 *
	 * @param int $id
	 *
	 * @return EventsModel
     */	
	public function eventById($id) 
	{
			
		return craft()->points->eventById($id);
	}
	
    /**
     * Entry - By ID
	 *
	 * @param int $id
	 *
	 * @return EntriesModel
     */	
	public function entryById($id) 
	{
			
		return craft()->points->entryById($id);
	}
	
    /**
     * Entries - Add
     *
	 * @param array $options
	 *
     */
	public function add($options)
	{
		return craft()->points->add($options);
	}
	
    /**
     * Entries - Remove
     *
	 * @param array $options
	 *
     */
	public function remove($options)
	{
		return craft()->points->remove($options);
	}
	
    /**
     * Entries - Total Sum
	 *
	 * @param int $userId
	 *
	 * @return string
     */	
	public function total($userId = null)
	{
		
		$userId = ($userId) ? $userId : craft()->points->userLoggedIn();
		return craft()->points->total($userId);
	}

}