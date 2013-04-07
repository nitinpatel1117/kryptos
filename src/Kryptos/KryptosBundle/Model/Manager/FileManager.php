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
}