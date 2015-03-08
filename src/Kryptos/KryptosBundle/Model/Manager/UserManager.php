<?php
namespace Kryptos\KryptosBundle\Model\Manager;

use Kryptos\KryptosBundle\Model\Manager\BaseManager;
use Kryptos\KryptosBundle\Model\User;
use Kryptos\KryptosBundle\Model\UserAccountActivation;
use Kryptos\KryptosBundle\Lib\Encryption;

class UserManager extends BaseManager
{
    const COLLECTION = 'user';
    
    protected $conversionCalculator = null;
    
    protected $conversionManager = null;


    public function __construct($mongoConnection, $conversionCalculator, $conversionManager)
    {
    	$dbCollection = $mongoConnection->connectToCollection(self::COLLECTION);

    	$this->setMongoCollection($dbCollection);
    	$this->setNameOfCollection(self::COLLECTION);
    	
    	$this->conversionCalculator = $conversionCalculator;
    	$this->conversionManager 	= $conversionManager;
    }


    public function createUserFrom($formData)
    {
    	$user = new User();
    	$user->setTitle(		$formData->getTitle());
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
    		$userItem = $this->createPublicPrivateKeys($userItem);

    		return parent::save($userItem);
    	}
    	
    	throw new \Exception('Account could not be activated');
    }
    
    
    /**
     * Function creates public and private keys for a user. User supplied as an array
     *  
     * @param array $userItem
     * @return array
     */
    public function createPublicPrivateKeys($userItem)
    {
    	$userItem['publicKey'] = new \MongoId();
    	$encryption = new Encryption();
    	$userItem['privateKey'] = $encryption->makePrivateKey();
    	return $userItem;
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
    	$user = $this->getUserByEmail($email, array('_id'));
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
    
    public function getUserByPublicKey($publicKey)
    {
    	$item = array('publicKey' => new \MongoId($publicKey));
    	$fields = array('privateKey'=>1, 'publicKey'=>1, 'email'=>1, 'credits'=>1);
    	return parent::findOne($item, $fields);
    }
    
    
    /* code to regenerate public and private keys for all users
    public function getAllUsers()
    {
    	$item = array();
    	$fields = array('firstName'=>1, 'email'=>1, 'publicKey'=>1, 'privateKey'=>1);
    	return parent::retrieve($item, $fields);
    }
    public function updateUserKeys($user)
    {
    	$query = array('_id' => $user['_id']);
    	$update = array(
    		'$set' => array(
    			'publicKey' => $user['publicKey'],
    			'privateKey' =>$user['privateKey'],
    		),
    	);
    	
    	return parent::update($query, $update);
    }
    */
    
    public function checkSignin($formData)
    {
    	$valid = false;
    	$user = new User();
    	$userArray = $this->getUserByEmail($formData->getEmail(), array('activation', 'salt', 'password', 'firstName'));

    	if (!is_null($userArray)) {
    		 if (true == $userArray['activation']['activated']) {
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

    	return array($valid, $user);
    }
    
    
    public function getUserVendorTxCode($VendorTxCode)
    {
    	$item = array('payment.VendorTxCode' => $VendorTxCode);
    	return parent::findOne($item);
    }
    
    
    public function getUserCredits($email)
    {
    	$credits = 0;
    	
    	$fields = array('credits'=>1);
    	$user = $this->getUserByEmail($email, $fields);
    	
    	if (isset($user['credits']) && is_numeric($user['credits'])) {
    		$credits = $user['credits'];
    	}
    	
    	return $credits;
    }
    
    
    public function registerCreditsUsed($userId, $fileId, $originalCredits, $credits)
    {
    	$creditsUsed = $originalCredits - $credits;
    	$costs = $this->getCostOfCreditsUsed($creditsUsed);
    	
    	// update user's conversion history
    	$conversion = array(
    		'creditsUsed' 	=> $creditsUsed,
    		'totalCost'		=> $costs,
    		'time'			=> new \MongoDate(),
    		'type'			=> 'batch',
    		'file'			=> $fileId,
    		'userId'		=> $userId,
    	);
    	$this->conversionManager->insert($conversion);
    	
    	// update user's credit
    	$query = array('_id' => new \MongoId($userId));
    	$update = array(
    		'$inc' => array ('credits' => ($creditsUsed * -1 ) ),
    	);

    	return parent::update($query, $update);
    }
    
    /**
     * FUnction returns what the equivilant cost would be if we calculated if from the number of conversions used 
     */
    public function getCostOfCreditsUsed($credits)
    {
    	$cost = 0;

    	$result = $this->conversionCalculator->calcRates($credits);
    	
    	if (isset($result['body']['cost']) && isset($result['body']['vat'])) {
    		$cost = $result['body']['cost'] + $result['body']['vat'];
    	}
    	
    	return $cost;
    }
    
    
    public function makePasswordResetDetails(array $userData)
    {
    	$encryption = new Encryption();
    	
    	$user = new User();
    	$user->setEmail($userData['email']);
    	list($code1, $code2) = $encryption->makeActivationCodes($user);
    
    	$passwordResetLink = sprintf('%s/%s/%s', $userData['_id']->{'$id'}, $code1, $code2);
    
    	$passReset = array();
    	$passReset['code1'] = $code1;
    	$passReset['code2'] = $code2;
    	$passReset['link'] = $passwordResetLink;
    	$passReset['sendDate'] = new \MongoDate();
    	$passReset['resetDone'] = false;
    	
    	
    	$query = array('_id' => $userData['_id']);
    	$update = array(
    		'$set' => array(
    			'passwordReset' => $passReset,
    		),
    	);
    	parent::update($query, $update);
    
    	return $passReset;
    }
    
    
    public function checkPasswordResetAccount($userId, $code1, $code2)
    {
    	$item = array(
    		'_id' => new \MongoId($userId),
    		'passwordReset.code1' => $code1,
    		'passwordReset.code2' => $code2
    	);
    	$userItem = parent::findOne($item, array('_id', 'passwordReset'));
    	 
    	if (is_null($userItem)) {
    		throw new \Exception('This password reset URL is NOT valid.');
    	}
    	 
    	if (true == $userItem['passwordReset']['resetDone']) {
    		throw new \Exception('Password has already been reset using this password reset URL.');
    	}
    
    	if (false == $userItem['passwordReset']['resetDone']) {
    		return array(true, $userItem);
    	}
    	 
    	throw new \Exception('Password could not be reset.');
    }
    
    
    public function doPasswordReset($userItem, $userPassword)
    {
    	// reset password account
    	$userItem['passwordReset']['resetDone'] = true;
    	$userItem['passwordReset']['resetDate'] = new \MongoDate();
    	
    	$encryption = new Encryption();
    	list($salt, $password) = $encryption->generateSaltAndPassword($userPassword);
    		
    	$query = array('_id' => $userItem['_id']);
    	$update = array(
    		'$set' => array(
    			'passwordReset' => $userItem['passwordReset'],
    			'salt' 			=> $salt,
    			'password' 		=> $password,
    		),
    	);
    	parent::update($query, $update);
    }
    
    
    /**
     * Function refunds credits to a users account
     * 
     * @param string $userId			The user id of the user to refund to 
     * @param MongoId $fileId			The file id of the uploaded file that we are refunding back for
     * @param int $amount				The amount of credits to refund
     */
    public function refundCredits($userId, $fileId, $amount)
    {
    	$costs = $this->getCostOfCreditsUsed($amount);
    	
    	// update user's conversion history
    	$conversion = array(
    		'creditsRefunded' 	=> $amount,
    		'totalCost'			=> $costs,
    		'time'				=> new \MongoDate(),
    		'type'				=> 'batch',
    		'file'				=> $fileId,
    		'userId'			=> $userId,
    	);
    	$this->conversionManager->insert($conversion);
    	
    	// update user's credit
    	$query = array('_id' => new \MongoId($userId));
    	$update = array(
    		'$inc' => array ('credits' => $amount ),
    	);
    	
    	return parent::update($query, $update);
    }
    
    
    /**
     * Function reduces a users credit by 1, and registers a conversion in the conversion table
     *
     * @param string $userId			The user id of the user to reduce credit from
     * @param string $type				The type of conversion to be recorded for this transaction, value of 'single' or 'singleApi'
     * @param int $amount				The amount of credits to reduce by
     */
    public function reduceCredit($userId, $type = 'single', $amount = 1)
    {
    	$costs = $this->getCostOfCreditsUsed($amount);
    	
    	// update user's conversion history
    	$conversion = array(
    		'creditsUsed' 		=> $amount,
    		'totalCost'			=> $costs,
    		'time'				=> new \MongoDate(),
    		'type'				=> $type,
    		'userId'			=> $userId,
    	);
    	$this->conversionManager->insert($conversion);
    
    	// update user's credit
    	$query = array('_id' => new \MongoId($userId));
    	$update = array(
    		'$inc' => array ('credits' => ($amount * -1) ),
    	);
    	
    	return parent::update($query, $update);
    }
    
    
    /**
     * Function updates an users personal data
     *
     * @param string $userId			The user id of the user to who we want to update
     * @param array $data				The new user data
     */
    public function updatePersonalDetails($userId, $data)
    {
    	$query = array('_id' => new \MongoId($userId));
    
    	if (isset($data['_id'])) {
    		unset($data['_id']);
    	}
    	
    	$update = array(
    		'$set' => $data,
    	);
    	 
    	return parent::update($query, $update);
    }
    
    
    /**
     * Retreive transaction details by user's email and transaction id
     * 
     * @param string $email					The user's email address
     * @param string $transactionId			The transaction id to search for
     */
    public function getTransactionDetails($email, $transactionId)
    {
    	$item = array(
    		'email' => $email,
    		'payment._id' => new \MongoId($transactionId)
    	);
    	
    	$fields = array(
    		'payment.$' => 1
    	);
    	
    	return parent::findOne($item, $fields);
    }
}