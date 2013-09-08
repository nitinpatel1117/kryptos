<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;

class TranslationManager extends BaseManager
{
    const COLLECTION = 'translation';


    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
    
    
    /**
     * Dangerous command to delete all data in translation collection.
     * 
     * NOTE: It is here on purpose, and not it the base class. As we dont want this command to be callable on other collections
     */
    public function drop()
    {
    	return $this->getMongoCollection()->drop();
    }
    
    
    
    public function findTranslation(array $translationData)
    {
    	$query = array('lang' => $translationData['lang'], 'name' =>$translationData['name']);
    	return parent::retrieve($query);
    }
    
    
    public function areTranslationsSame(array $newTranslation, array $oldTranslation)
    {
    	$same = false;
    	
    	if ($newTranslation['lang'] === $oldTranslation['lang'] && $newTranslation['name'] === $oldTranslation['name'] && $newTranslation['value'] === $oldTranslation['value']) {
    		$same = true;
    	}
    		
    	return $same;
    }
    
    
    public function remove($translationData)
    {
    	$query = array('lang' => $translationData['lang'], 'name' =>$translationData['name']);
    	return $this->getMongoCollection()->remove($query, array("w" => 1, "j" => true));
    }
}