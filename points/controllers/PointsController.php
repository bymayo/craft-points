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

class PointsController extends BaseController
{

    /**
     * Allows anonymous access to this controller's actions.
     */
    protected $allowAnonymous = array('actionIndex',);

    /**
     * Save Event
     */
    public function actionSaveEvent()
    {
        $this->requirePostRequest();

        $id = craft()->request->getPost('id');
        if ($id) {
            $event = craft()->points->eventById($id);
        }
        else {
            $event = new Points_EventsModel();
        }

        $attributes = craft()->request->getPost();
        
        $event->setAttributes(array(
            'event' => $attributes['event'],
            'eventHandle' => $attributes['eventHandle'],
            'points' => $attributes['points'],
            'multiple' => $attributes['multiple']
        ));

        if (craft()->points->saveEvent($event)) {
            craft()->userSession->setNotice(Craft::t('Event saved.'));
            $this->redirectToPostedUrl($event);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save Event.'));
        }
    }
    
	/**
     * Save Entry
     */
    public function actionSaveEntry()
    {
        $this->requirePostRequest();

        $id = craft()->request->getPost('id');
        if ($id) {
            $entry = craft()->points->getEntryById($id);
        }
        else {
            $entry = new Points_EntriesModel();
        }

        $attributes = craft()->request->getPost();
        
        $entry->setAttributes(array(
            'user' => $attributes['user'],
            'eventHandle' => $attributes['eventHandle']
        ));

        if (craft()->points->saveEntry($entry)) {
            craft()->userSession->setNotice(Craft::t('Entry saved.'));
            $this->redirectToPostedUrl($entry);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save Entry.'));
        }
    }
    
    /*
	* Delete Event
	*/
	public function actionDeleteEvent($id)
	{
		
        $eventRecord = Points_EventsRecord::model()->findByAttributes(array('id' => $id));

        if ($eventRecord)
        {
            $eventRecord->delete();
        }
        
        craft()->userSession->setNotice(Craft::t('Event Deleted'));
        $this->redirect('points/events');
		
	}
	
    /*
	* Delete Entry
	*/
	public function actionDeleteEntry($id)
	{
		
        $entryRecord = Points_EntriesRecord::model()->findByAttributes(array('id' => $id));

        if ($entryRecord)
        {
            $entryRecord->delete();
        }
        
        craft()->userSession->setNotice(Craft::t('Entry Deleted'));
        $this->redirect('points/entries');
		
	}


}