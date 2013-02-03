<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('username', 		 'text', array(
    		'label'=>'Username',
    		'required' => true,
    		'constraints' => array(
	        	new NotBlank(),
	        	new Length(array('min' => 4, 'max' => 20)),
	        	new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'Username should only contain alphanumber characters. (a to z) and (0 to 9)')),
			)));
			
		$builder->add('firstName', 		 'text', array('label'=>'Firstname', 'required' => true));
		$builder->add('lastName', 		 'text', array('label'=>'Lastname', 'required' => true));
		$builder->add('jobTitle', 		 'text', array('label'=>'Jobtitle', 'required' => true));
		$builder->add('company', 		 'text', array('label'=>'Company', 'required' => true));
		$builder->add('location', 		 'text', array('label'=>'Location', 'required' => true));
		
		$builder->add('email', 'repeated', array(
			'type' => 'text',
			'required' => true,
			'invalid_message' => 'The email fields do not match.',
			'first_name' => 'email',
			'second_name' => 'confirmEmail',
			'first_options' => array('label' => 'Email'),
		    'second_options' => array('label' => 'Confirm Email'),
		));
		
		$builder->add('password', 'repeated', array(
			'type' => 'password',
			'required' => true,
			'invalid_message' => 'The password fields do not match',
			'first_name' => 'password',
			'second_name' => 'confirmPassword',
			'first_options' => array('label' => 'Password'),
		    'second_options' => array('label' => 'Confirm Password'),
			'constraints' => array(
	           new NotBlank(),
	           new Length(array('min' => 4, 'max' => 20)),
	           new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'Password should only contain alphanumber characters. (a to z) and (0 to 9)')),
			)
		));
		
		$builder->add('captcha', 'captcha', array(
			'invalid_message' => 'Incorrect code entered.',
			'as_url' => true,
			'reload' => true,
			'label' => 'Word Verification',
		));
		
		$builder->add('acceptTerms', 'checkbox', array(
			'label' => 'I agree to the Kryptos Terms of Service and Privacy Policy',
			'required' => true,
		));
		

		
		
		#$builder->add('key', 'text');
    }
    

    public function getName()
    {
        return 'RegistrationForm';
    }


	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\Register',
			)
		);
	}
}