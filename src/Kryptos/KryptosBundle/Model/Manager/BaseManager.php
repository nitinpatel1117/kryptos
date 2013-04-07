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


	public function insert($item)
	{
		return $this->getMongoCollection()->insert($item, array("w" => 1, "j" => true));
	}
	
	public function batchIsert($items)
	{
		return $this->getMongoCollection()->batchInsert($items, array("w" => 1, "j" => true));
	}

	public function save($item)
	{
		return $this->getMongoCollection()->save($item, array("w" => 1, "j" => true));
	}
	
	public function findOne($query)
	{
		return $this->getMongoCollection()->findOne($query);
	}
	
	public function find($query, $fields = array())
	{
		return $this->getMongoCollection()->find($query, $fields);
	}

}