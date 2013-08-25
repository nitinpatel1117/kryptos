<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class SettingsUserDetailsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
				'placeholder'			=> 'Forename',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Forename',
			),
		));
		
		$builder->add('lastName', 'text', array(
			'label'=>'Lastname',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'Surname',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Surname',
			),
		));
		
		$builder->add('jobTitle', 'text', array(
			'label'=>'Jobtitle',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'Jobtitle',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Jobtitle',
			),
		));
		
		$builder->add('company', 'text', array(
			'label'=>'Company',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'Company',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Company',
			),
		));
		
		$builder->add('location', 'text', array(
			'label'=>'Location',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'Location',
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
					'placeholder'			=> 'Email',
					'rel'					=> 'tooltip',
		#			'data-original-title'	=> 'Please enter your Email',
				),
			),
		    'second_options' => array(
		    	'label' => 'Confirm Email',
		    	'attr' => array(
		    		'placeholder' 			=> 'Confirm Email',
		    		'rel'					=> 'tooltip',
		#    		'data-original-title'	=> 'Please confirm your Email',
		    	),
		    ),
		));
    }


    public function getName()
    {
        return 'SettingsUserDetailsForm';
    }


	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\SettingsUserDetails',
			)
		);
	}
}