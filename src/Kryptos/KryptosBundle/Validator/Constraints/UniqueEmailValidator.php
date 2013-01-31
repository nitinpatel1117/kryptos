<?php
namespace Kryptos\KryptosBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailValidator extends ConstraintValidator
{
	protected $userManager;
	
	public function __construct($userManager)
	{
		$this->userManager = $userManager;
	}
	
    public function validate($value, Constraint $constraint)
    {
    	if ($this->userManager->isEmailTaken($value)) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }
}