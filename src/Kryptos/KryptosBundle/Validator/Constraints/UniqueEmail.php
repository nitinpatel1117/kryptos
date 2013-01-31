<?php
namespace Kryptos\KryptosBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueEmail extends Constraint
{
	public $message = 'The email "%string%" already exists';
	
	public function validatedBy()
	{
	    return 'UniqueEmailValidator';
	}
}