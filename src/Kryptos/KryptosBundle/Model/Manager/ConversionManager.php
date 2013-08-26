<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;

class ConversionManager extends BaseManager
{
    const COLLECTION = 'conversion';


    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
    
    
    public function getItemsByUserId($userId, $fields = array())
    {
    	$query = array('userId' => $userId);
    	return parent::retrieve($query, $fields);
    }
}