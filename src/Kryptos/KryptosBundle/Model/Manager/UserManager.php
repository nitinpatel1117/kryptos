<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;
use Kryptos\KryptosBundle\Model\User;

class UserManager extends BaseManager
{
    const COLLECTION = 'User';
    
    
    public function __construct($mongoConnection)
    {
    	# $dbCollection = $this->get('mongo_connection')->connectToCollection(self::COLLECTION);
    	
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);
    	
    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }
    
    
    public function createUserFrom($formData)
    {
    	$user = new User();
    	$user->setUsername(		$formData->getUsername());
    	$user->setFirstName(	$formData->getFirstName());
		$user->setLastName(		$formData->getLastName());
		$user->setJobTitle(		$formData->getJobTitle());
		$user->setCompany(		$formData->getCompany());
		$user->setLocation(		$formData->getLocation());
		$user->setEmail(		$formData->getEmail());
		$user->setPassword(		$formData->getPassword());
		$user->setAcceptTerms(	$formData->getAcceptTerms());
		
		return $user;
    }
    
}