<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;

class SageNotificationManager extends BaseManager
{
    const COLLECTION = 'sage_notification';


    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
}