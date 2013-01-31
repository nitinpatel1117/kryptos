<?php
namespace Kryptos\KryptosBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUsernameValidator extends ConstraintValidator
{
	protected $userManager;
	
	protected $configManager;
	
	
	public function __construct($userManager, $configManager)
	{
		$this->userManager = $userManager;
		$this->configManager = $configManager;
	}
	
    public function validate($value, Constraint $constraint)
    {
    	if ($this->userManager->isUsernameTaken($value)) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }
}