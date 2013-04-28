<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;
use Kryptos\KryptosBundle\Model\User;
use Kryptos\KryptosBundle\Model\UserAccountActivation;
use Kryptos\KryptosBundle\Lib\Encryption;

class UserManager extends BaseManager
{
    const COLLECTION = 'user';


    public function __construct($mongoConnection)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    }


    public function createUserFrom($formData)
    {
    	$user = new User();
    	$user->setFirstName(	$formData->getFirstName());
		$user->setLastName(		$formData->getLastName());
		$user->setJobTitle(		$formData->getJobTitle());
		$user->setCompany(		$formData->getCompany());
		$user->setLocation(		$formData->getLocation());
		$user->setEmail(		$formData->getEmail());
		$user->setAcceptTerms(	$formData->getAcceptTerms());

		$encryption = new Encryption();
		list($salt, $password) = $encryption->generateSaltAndPassword($formData->getPassword());

		$user->setSalt(			$salt);
		$user->setPassword(		$password);

		return $user;
    }


    public function register($user)
    {
    	parent::insert($user);
    	$activationDetails = $this->makeActivationDetails($user);
    	$user->setActivation($activationDetails);
    	parent::save($user);
    }


    public function makeActivationDetails($user)
    {
    	$encryption = new Encryption();
    	list($code1, $code2) = $encryption->makeActivationCodes($user);

    	$activationLink = sprintf('%s/%s/%s', $user->getId(), $code1, $code2);

    	$activation = new UserAccountActivation();
    	$activation->setCode1($code1);
    	$activation->setCode2($code2);
    	$activation->setLink($activationLink);
    	$activation->setSendDate(new \MongoDate());
    	$activation->setActivated(false);

    	return $activation;
    }
    
    
    public function activateAccount($userId, $code1, $code2)
    {	
    	$item = array(
    		'_id' => new \MongoId($userId),
    		'activation.code1' => $code1,
    		'activation.code2' => $code2
    	);
    	$userItem = parent::findOne($item);
    	
    	if (is_null($userItem)) {
    		throw new \Exception('This activation URL is NOT valid');
    	}
    	
    	if (true == $userItem['activation']['activated']) {
    		throw new \Exception('Account has already been activated');
    	}

    	if (false == $userItem['activation']['activated']) {
    		// activate account
    		$userItem['activation']['activated'] = true;
    		$userItem['activation']['activateDate'] = new \MongoDate();

    		return parent::save($userItem);
    	}
    	
    	throw new \Exception('Account could not be activated');
    }


    /**
     * Fucntion checks if the suppllied username is taken. By default we return true in case we couldn't access mongo for any reason
     *
     * @param $username string		The username that we want to search for
     * @return bool
     *
    public function isUsernameTaken($username)
    {
    	$usernameTaken = true;
    	$user = $this->getUserByUsername($username);
    	if (is_null($user)) {
    		$usernameTaken = false;
    	}

    	return $usernameTaken;
    }*/

	/**
     * Fucntion checks if the suppllied email is taken. By default we return true in case we couldn't access mongo for any reason
     *
     * @param $email string		The email that we want to search for
     * @return bool
     */
    public function isEmailTaken($email)
    {
    	$emailTaken = true;
    	$user = $this->getUserByEmail($email);
    	if (is_null($user)) {
    		$emailTaken = false;
    	}

    	return $emailTaken;
    }

    /*
	public function getUserByUsername($username)
    {
    	$object = array('username' => $username);
    	return $this->getMongoCollection()->findOne($object);
    }
    */


	public function getUserByEmail($email, array $fields = array())
    {
    	$item = array('email' => $email);
    	return parent::findOne($item, $fields);
    }
    
    public function getUserById($id)
    {
    	$item = array('_id' => $id);
    	return parent::findOne($item);
    }


    
    public function checkSignin($formData)
    {
    	$valid = false;
    	$userArray = $this->getUserByEmail($formData->getEmail());

    	if (!is_null($userArray)) {
    		 if (true == $userArray['activation']['activated']) {
    		 	$user = new User();
    		 	foreach ($userArray as $key => $value) {
    		 		$user->$key = $value;
    		 	}
    		 		
    		 	$encryption = new Encryption();
    		 	$valid = $encryption->isPasswordValid($formData->getPassword(), $user);
    		 }
    		 else {
    		 	$valid = "not_activated";
    		 }
    	}

    	return $valid;
    }
    
    
    public function getUserVendorTxCode($VendorTxCode)
    {
    	$item = array('payment.VendorTxCode' => $VendorTxCode);
    	return parent::findOne($item);
    }
    
    
    
    public function registerCreditsUsed($userId, $fileId, $originalCredits, $credits)
    {
    	$creditsUsed = $originalCredits - $credits;

    	$query = array('_id' => new \MongoId($userId));
    	
    	$update = array(
    		'$push' => array(
    			'conversionHistory' => array(
    				'creditsUsed' 	=> $creditsUsed,
    				'time'			=> new \MongoDate(),
    				'type'			=> 'batch',
    				'file'			=> $fileId,
    			)
    		), 
    		'$inc' => array ('credits' => ($creditsUsed * -1 ) ),
    	);

    	return parent::update($query, $update);
    }
}