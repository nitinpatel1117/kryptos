<?php
namespace Kryptos\KryptosBundle\Model\Base;

class Base
{
    protected $nameOfCollection;
    
    /**
     * The actual mongo collection
     * @var MongoCollection
     */
    protected $mongoCollection;
    
    
	public function setNameOfCollection($nameOfCollection) {
		$this->nameOfCollection = $nameOfCollection;
	}
	public function getNameOfCollection() {
		return $this->nameOfCollection;
	}
	
	public function setMongoCollection($mongoCollection) {
		$this->mongoCollection = $mongoCollection;
	}
	public function getMongoCollection() {
		return $this->mongoCollection;
	}
    
}