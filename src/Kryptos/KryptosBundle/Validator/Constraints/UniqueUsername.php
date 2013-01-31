<?php
namespace Kryptos\KryptosBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueUsername extends Constraint
{
	public $message = 'The username "%string%" already exists please choose another';
	
	public function validatedBy()
	{
	    return 'UniqueUsernameValidator';
	}
}