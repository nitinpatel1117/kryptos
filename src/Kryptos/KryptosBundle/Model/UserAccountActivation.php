<?php
namespace Kryptos\KryptosBundle\Model;

class UserAccountActivation
{
    public $code1;

    public $code2;

    public $link;

    public $sendDate;

    public $activated;
    
    public $activateDate;


    public function __construct()
    {
    }


	public function setCode1($code1) {
		$this->code1 = $code1;
	}
	public function getCode1() {
		return $this->code1;
	}

	public function setCode2($code2) {
		$this->code2 = $code2;
	}
	public function getCode2() {
		return $this->code2;
	}

	public function setLink($link) {
		$this->link = $link;
	}
	public function getLink() {
		return $this->link;
	}

	public function setSendDate($sendDate) {
		$this->sendDate = $sendDate;
	}
	public function getSendDate() {
		return $this->sendDate;
	}

	public function setActivated($activated) {
		$this->activated = $activated;
	}
	public function getActivated() {
		return $this->activated;
	}
	
	public function setActivateDate($activateDate) {
		$this->activateDate = $activateDate;
	}
	public function getActivateDate() {
		return $this->activateDate;
	}

}