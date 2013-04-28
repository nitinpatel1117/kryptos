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
	
	public function findOne($query, array $fields = array())
	{
		return $this->getMongoCollection()->findOne($query, $fields);
	}
	
	/**
	 * NOTE: this function returns a MongoCursor. to access the results we need to loop through the results
	 * 
	 * @param unknown $query
	 * @param array $fields
	 */
	public function find($query, array $fields = array(), $limit=false)
	{
		$cursor = $this->getMongoCollection()->find($query, $fields);
		
		if (false !== $limit && is_int($limit)) {
			$cursor->limit($limit);
		}
		
		return $cursor;
		
	}
	
	/**
	 * This function loops through the MongoCursor returned from the find query and returns each item as an object in an array
	 * 
	 * @param Mixed $query
	 * @param array $fields
	 */
	public function retrieve($query, array $fields = array(), $limit=false)
	{
		$data = array();
		
		$cursor = $this->find($query, $fields, $limit);
		while ($cursor->hasNext()) {
			$data[] = $cursor->getNext();
		}
		
		return $data;
	}
	

	public function count($query, $limit = 0, $skip = 0)
	{
		return $this->getMongoCollection()->count($query, $limit, $skip);
	}
	
	
	public function update($criteria, $newObject = array(), $options = array())
	{		
		return $this->getMongoCollection()->update($criteria, $newObject, $options);
	}
	
	public function findAndModify (array $query, array $update)
	{
		return $this->getMongoCollection()->findAndModify ($query, $update);
	}
	

}