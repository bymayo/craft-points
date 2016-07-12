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

class PointsService extends BaseApplicationComponent
{
	
    /**
     * Entries - List
     *
	 * @return EntriesModel
     */
	public function entries()
	{
		$defaults = craft()->db->createCommand()
			->select('*')
			->from('points_entries')
			->order('dateCreated')
			->queryAll();

		$model = Points_EntriesModel::populateModels($defaults);

		return $model;
	}
	
    /**
     * Events - List
	 *
	 * @return EventsModel
     */	
	public function events()
	{
		$defaults = craft()->db->createCommand()
			->select('*')
			->from('points_events')
			->order('dateCreated')
			->queryAll();

		$model = Points_EventsModel::populateModels($defaults);

		return $model;
	}
	
    /**
     * User - Logged In
	 *
	 * @return string
     */
	public function userLoggedIn()
	{
		$user = craft()->userSession->getUser();
		return $user->id;
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
		$regexId = preg_replace('/[^0-9]/', "", $userId); // Hack around to get ["1"] into 1
		$user = craft()->users->getUserById($regexId);
		return $user;
	}

    /**
     * Entries - Add
     *
	 * @param array $options
	 *
     */
	public function addEntry($options)
	{
		
		// Check to see if event allows multiple 
		$eventsRecord = Points_EventsRecord::model()->findByAttributes(
															array(
																'eventHandle' => $options['eventHandle'],
																'multiple' => 1
															)
														);

		// Check if entry already exists 
        $entriesRecord = Points_EntriesRecord::model()->findByAttributes(
						        							array(
						        								'eventHandle' => $options['eventHandle'],
						        								'user' => (isset($options['userId']) == true) ? $options['userId'] : craft()->points->userLoggedIn()
						        							)
														);

        if ($entriesRecord && !$eventsRecord) {
	        // return 'Entry already exists';
		}
		else {
            $entriesRecord = new Points_EntriesRecord;
            $entriesRecord->setAttribute('user', (isset($options['userId']) == true) ? $options['userId'] : craft()->points->userLoggedIn());
            $entriesRecord->setAttribute('eventHandle', $options['eventHandle']);
			$entriesRecord->save();
            // return 'Entry Added';     
        }

	}
	
    /**
     * Entries - Remove
     *
	 * @param array $options
	 *
     */
	public function removeEntry($options) 
	{
		
        $entriesRecord = Points_EntriesRecord::model()->findByAttributes(
						        							array(
						        								'eventHandle' => $options['eventHandle'],
						        								'user' => (isset($options['userId']) == true) ? $options['userId'] : craft()->points->userLoggedIn()
						        							)
						        						);
        						
        if ($entriesRecord) {
			$entriesRecord->delete(); 
            // return 'Entry Removed';  
        }
		
	}
	
    /**
     * Event - Save
     *
	 * @param array $event
	 *
	 * @return EventsRecord
     */
    public function saveEvent(Points_EventsModel $event)
    {

        if ($event->id) {
            $eventRecord = Points_EventsRecord::model()->findById($event->id);
            if (!$eventRecord) {
                throw new Exception(Craft::t('No event exists with the ID “{id}”.', array('id' => $event->id)));
            }
        }
        else {
            $eventRecord = new Points_EventsRecord();
        }

        $eventRecord->setAttributes($event->getAttributes());
        $eventRecord->validate();
        $event->addErrors($eventRecord->getErrors());

        if (!$event->hasErrors()) {
            return $eventRecord->save();
        }
        return false;
    }
    
    /**
     * Event - Add
     *
	 * @param array $options
	 *
     */
     public function addEvent($options) {
	     
        $eventRecord = Points_EventsRecord::model()->findByAttributes(
						        							array(
						        								'eventHandle' => $options['eventHandle']
						        							)
														);
														
		if (!$eventRecord) {
			
	        $event = new Points_EventsModel();
		     
	        $event->setAttributes(array(
	            'event' => $options['event'],
	            'eventHandle' => $options['eventHandle'],
	            'points' => $options['points'],
	            'multiple' => $options['multiple']
	        ));
		     
			craft()->points->saveEvent($event);
			
		}
	     
     }
    
    /**
     * Entry - Save
     *
	 * @param array $entry
	 *
	 * @return EntriesRecord
     */
    public function saveEntry(Points_EntriesModel $entry)
    {

        if ($entry->id) {
            $entryRecord = Points_EntriesRecord::model()->findById($entry->id);
            if (!$entryRecord) {
                throw new Exception(Craft::t('No entry exists with the ID “{id}”.', array('id' => $entry->id)));
            }
        }
        else {
            $entryRecord = new Points_EntriesRecord();
        }

        $entryRecord->setAttributes($entry->getAttributes());
        $entryRecord->validate();
        $entry->addErrors($entryRecord->getErrors());

        if (!$entry->hasErrors()) {
            return $entryRecord->save();
        }
        return false;
    }
    
    /**
     * Event - Options
	 *
	 * @return array
     */
    public function eventOptions()
    {
	    
		$result = craft()->db->createCommand()
			->select('*')
			->from('points_events')
			->order('dateCreated')
			->queryAll();
	    
	    $options = [
	    	[
	        	'label' => '----', 
				'value' => ''
			]
		];
	    
	    foreach ($result as $field)
	    {
	        $options[] = [
	            'label' => $field['event'], 
	            'value' => $field['eventHandle']
	        ];
	    }
	    
	    return $options;
	    
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
        
        $eventModel = new Points_EventsModel();
        
        $eventRecord = Points_EventsRecord::model()->findByAttributes(array('eventHandle' => $handle));

        if ($eventRecord)
        {
            $eventModel = Points_EventsModel::populateModel($eventRecord);
        }

        return $eventModel;
	    
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
        
        $eventModel = new Points_EventsModel();
        
        $eventRecord = Points_EventsRecord::model()->findByAttributes(array('id' => $id));

        if ($eventRecord)
        {
            $eventModel = Points_EventsModel::populateModel($eventRecord);
        }

        return $eventModel;
	    
    }
    
    /**
     * Event - By Handle
	 *
	 * @param string $eventHandle
	 *
	 * @return EventsModel
     */	
    public function eventByHandle($eventHandle)
    {
        
        $eventModel = new Points_EventsModel();
        
        $eventRecord = Points_EventsRecord::model()->findByAttributes(array('eventHandle' => $eventHandle));

        if ($eventRecord)
        {
            $eventModel = Points_EventsModel::populateModel($eventRecord);
        }

        return $eventModel;
	    
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
        
        $entryModel = new Points_EntriesModel();
        
        $entryRecord = Points_EntriesRecord::model()->findByAttributes(array('id' => $id));

        if ($entryRecord)
        {
            $entryModel = Points_EntriesModel::populateModel($entryRecord);
        }

        return $entryModel;
	    
    }
    
    /**
     * Entries - By User
	 *
	 * @param int $id
	 *
	 * @return EntriesModel
     */	
    public function entriesByUser($userId)
    {
	    
		$defaults = craft()->db->createCommand()
			->select('*')
			->from('points_entries')
			->where('user=' . $userId)
			->order('dateCreated')
			->queryAll();

		$model = Points_EntriesModel::populateModels($defaults);

		return $model;
	    
    }
    
    /**
     * Entries - Sum
	 *
	 * @param int $userId
	 *
	 * @return string
     */	
    public function sumEntries($userId) {

		$result = craft()->db->createCommand()
			->select('entries.user, sum(events.points) as count')
			->from('points_events events')
			->join('points_entries entries', 'events.eventHandle=entries.eventHandle')
			->group('entries.user')
			->where('entries.user=' . $userId)
			->queryAll();
			
		if (count($result) === 0) {
			$count = count($result);
		}
		else {
			$count = $result[0]['count'];	
		}
	    
	    return $count;
	    
    }
    
    /**
     * Entries - Total
	 *
	 * @param int $userId
	 *
	 * @return string
     */	
    public function totalEntries($userId) {

		$result = craft()->db->createCommand()
			->select('*')
			->from('points_entries')
			->where('user=' . $userId)
			->queryAll();
	    
	    return count($result);
	    
    }
	    

}