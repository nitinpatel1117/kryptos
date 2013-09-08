<?php

namespace Kryptos\KryptosBundle\Services;


class MongoConnection
{
	protected $allowedCollections = array('user', 'sage_notification', 'cache', 'file', 'file_error', 'bank_account', 'conversion', 'translation');
	
	protected $serviceContainer;
	
	protected $connectionParams;
	
	protected $mongoClient;
	
	protected $mongoDB;
	
    public function __construct($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }
    
    
    public function getDB()
    {
    	if (is_null($this->mongoDB)){
	    	$this->retrieveMongoConnectionParams();
	    	$this->createConnection();
	    	$this->connectToDatabase();
    	}
    	
    	if (is_null($this->mongoDB)){
    		// log this in future
    		die('mongo DB could not be initialised');
    	}
    	
    	return $this->mongoDB;
    }
    
    
	public function retrieveMongoConnectionParams()
    {
    	if (is_null($this->connectionParams)) {
	    	if ($this->serviceContainer->hasParameter('mongo_connection')) {
	    		$this->connectionParams = $this->serviceContainer->getParameter('mongo_connection');
	    	}
    	}
    }
    
    
	public function createConnection()
    {
    	if (isset($this->connectionParams['server'])) {
    		$this->mongoClient = new \MongoClient($this->connectionParams['server']);
    	}

    	if (is_null($this->mongoClient)) {
    		// log this in future
    		die('mongo client could not be set');
    	}
    }
    
    
    public function connectToDatabase()
    {
    	if (isset($this->connectionParams['default_db'])) {
    		$dbname = $this->connectionParams['default_db'];
    		$this->mongoDB = $this->mongoClient->selectDB($dbname);
    	}
    	
    	if (is_null($this->mongoDB)) {
    		// log this in future
    		die('mongo DB could not be set');
    	}
    }


    public function connectToCollection($collectionName)
    {    	
    	if (!in_array($collectionName, $this->allowedCollections)) {
    		die('collection does not exist in DB');
    	}
    	
    	return $this->getDB()->selectCollection($collectionName);
    }
}