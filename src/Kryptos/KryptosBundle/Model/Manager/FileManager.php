<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;

class FileManager extends BaseManager
{
    const COLLECTION = 'file';


    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
    

    public function getFilesByUser($userId)
    {
    	$item = array('userId' => $userId);
    	return parent::find($item)->limit(20)->sort(array('upload_time' => 1));
    }
    
    
    public function getFilesBySession($sessionId)
    {
    	$item = array('sessionId' => $sessionId);
    	return parent::find($item)->limit(20)->sort(array('upload_time' => 1));
    }
    
    
    public function getFileById($id)
    {
    	$item = array('_id' => new \MongoId($id));
    	return parent::findOne($item);
    }
    
    
    public function lockItemsFromQueue($lockTime, $lockName, $limit = 1000)
    {
    	$ownItemsLocked = $this->countOwnlockedItems($lockName);
    	$itemsTolock = $limit - $ownItemsLocked;
    	 
    	// how many more rows do we need?
    	if ($itemsTolock > 0) {
    		$this->lockItems($lockTime, $lockName, $itemsTolock);
    	}
    	 
    
    	return $this->getOwnLockedItems($lockName, $limit);
    }
    
    
    
    
    /**
     * Function locks a set amount of items for the current machine
     */
    public function lockItems($lockTime, $lockName, $limit)
    {
    	$timeNow = time();
    	$unlockTime = $timeNow + $lockTime;
    	 
    	$query = $this->queryFindUnlockedItems($lockName);
    	$update = $this->queryLockItems($lockName, $unlockTime);
    	 
    	for ($x = 0; $x < $limit; $x++) {
    		$this->findAndModify ($query, $update);
    	}
    }
    
    public function getOwnLockedItems($lockName, $limit)
    {
    	$query = $this->queryGetOwnLockedItems($lockName);
    	$fields = array();
    	return $this->retrieve($query, $fields, $limit);
    }
    
    public function countOwnlockedItems($lockName)
    {
    	$query = $this->queryGetOwnLockedItems($lockName);
    	return $this->count($query);
    }
    
    
    /**
     * Creates a query that retrieves items that are locked for the supplied locked
     *
     * @param string $lockName
     * @return marray
     */
    public function queryGetOwnLockedItems($lockName)
    {
    	$query = array(
    		'lockname'  => $lockName,
    		'status'	=> 'pending',
    		# 		'$or' => array(
    		# 			array("locktime" => array ('$exists'=> false) ),
    		# 			array("locktime" => array ('$lt'=> new \MongoDate()))
    	# 	    ),
    	);
    	 
    	return $query;
    }
    
    /**
     * Creates a query that finds items that are available to process
     *
     * @param string $lockName
     * @return marray
     */
    public function queryFindUnlockedItems($lockName)
    {
    	$query = array(
    		'lockname'  => array('$ne' => $lockName),
    		'status'	=> 'pending',
    		'$or' => array(
    			array("locktime" => array ('$exists'=> false) ),
    			array("locktime" => array ('$lt'=> new \MongoDate()))
    		),
    	);
    
    	return $query;
    }
    
    /**
     * update part of the query that locks an item
     *
     * @return array
     */
    public function queryLockItems($lockName, $lockTime)
    {
    	$update = array(
    		'$set' => array(
    			'lockname' => $lockName,
    			'locktime' => new \MongoDate($lockTime),
    		)
    	);
    	 
    	return $update;
    }
}