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
    	/*
    	$builder->add('username', 		 'text', array(
    		'label'=>'Username',
    		'required' => true,
    		'constraints' => array(
	        	new NotBlank(),
	        	new Length(array('min' => 4, 'max' => 20)),
	        	new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'Username should only contain alphanumber characters. (a to z) and (0 to 9)')),
			)));
			*/

    	
    	$titles = array(
    		'Mr' 	=> 'Mr.',
    		'Mrs' 	=> 'Mrs.',
    		'Miss' 	=> 'Miss',
    		'Ms' 	=> 'Ms.',
    		'Dr' 	=> 'Dr.',
    		'Prof' 	=> 'Prof.',
    		'Rev' 	=> 'Rev.',
    		'Other' => 'Other',
    	);
    	
    	$builder->add('title', 'choice', array(
    		'choices'   => $titles,
    	#	'required'  => true,
    		'empty_value' => ' - Title - ',
    	));

		$builder->add('firstName', 'text', array(
			'label'=>'Firstname',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_forename',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Forename',
			),
		));
		
		$builder->add('lastName', 'text', array(
			'label'=>'Lastname',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_surname',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Surname',
			),
		));
		
		$builder->add('jobTitle', 'text', array(
			'label'=>'Jobtitle',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_job_title',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Jobtitle',
			),
		));
		
		$builder->add('company', 'text', array(
			'label'=>'Company',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_company',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Company',
			),
		));
		
		$builder->add('location', 'text', array(
			'label'=>'Location',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_location',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Location',
			),
		));

		$builder->add('email', 'repeated', array(
			'type' => 'text',
		#	'required' => true,
			'invalid_message' => "Email's do not match |The Email addresses you have typed do not match. Please check and try again.",
			'first_name' => 'email',
			'second_name' => 'confirmEmail',
			'first_options' => array(
				'label' => 'Email',
				'attr' => array(
					'placeholder'			=> 'txt_email',
					'rel'					=> 'tooltip',
		#			'data-original-title'	=> 'Please enter your Email',
				),
			),
		    'second_options' => array(
		    	'label' => 'Confirm Email',
		    	'attr' => array(
		    		'placeholder' 			=> 'txt_confirm_email',
		    		'rel'					=> 'tooltip',
		#    		'data-original-title'	=> 'Please confirm your Email',
		    	),
		    ),
		));

		$builder->add('password', 'repeated', array(
			'type' => 'password',
		#	'required' => true,
			'invalid_message' => "Password's do not match |The Passwords you have typed do not match. Please check and try again.",
			'first_name' => 'password',
			'second_name' => 'confirmPassword',
			'first_options' => array(
				'label' => 'Password',
				'attr' => array(
					'placeholder' 			=> 'txt_password',
					'rel'					=> 'tooltip',
		#			'data-original-title'	=> 'Please enter your Password',
					'autocomplete'			=> 'off',
				),
			),
		    'second_options' => array(
		    	'label' => 'Confirm Password',
		    	'attr' => array(
		    		'placeholder' 			=> 'txt_confirm_password',
		    		'rel'					=> 'tooltip',
		#    		'data-original-title'	=> 'Please confirm your Password',
		    		'autocomplete'			=> 'off',
		    	),
		    ),
			'constraints' => array(
				new NotBlank(),
				new Length(array(
					'min' => 4, 
					'max' => 20,
					'maxMessage' => 'Invalid password|Password value is too long. It should have {{ limit }} characters or less.',
					'minMessage' => 'Invalid password|Password value is too short. It should have {{ limit }} characters or more.',
				)),
				new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'Invalid password|Password should only contain alphanumber characters. (a to z) and (0 to 9)')),
			)
		));

		$builder->add('captcha', 'captcha', array(
			'invalid_message' => 'Character mismatch|The characters that you have entered do not match with the ones shown in the image. Please try again.',
			'as_url' => true,
			'reload' => true,
			'label' => 'Word Verification',
			'background_color' => array(255, 255, 255),
			'attr' => array(
				'placeholder' 			=> 'Enter text',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter the text displayed in the image',
				'autocomplete'			=> 'off',
			),
		));

		/*
		$builder->add('acceptTerms', 'checkbox', array(
			'label' => 'I agree to the Kryptos Terms of Service and Privacy Policy',
			'required' => true,
		));
		*/
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