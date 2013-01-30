<?php
namespace Kryptos\KryptosBundle\Model\Manager;

class BaseManager
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
	
	
	public function save($item)
	{
		echo "<BR>";
		var_dump('item', $item);
		
		$this->getMongoCollection()->save($item, array("w" => 1));
		
		echo "<BR>";
		var_dump('item', $item);
		echo "<BR><BR>SAVED TO MONGODB<BR>";
	}
    
}