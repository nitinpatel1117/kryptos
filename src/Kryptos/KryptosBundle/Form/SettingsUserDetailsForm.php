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
    		'Mr' 	=> 'txt_title_item_mr',
    		'Mrs' 	=> 'txt_title_item_mrs',
    		'Miss' 	=> 'txt_title_item_miss',
    		'Ms' 	=> 'txt_title_item_ms',
    		'Dr' 	=> 'txt_title_item_dr',
    		'Prof' 	=> 'txt_title_item_prof',
    		'Other' => 'txt_title_item_other',
    	);
    	
    	$builder->add('title', 'choice', array(
    		'label'		=> 'txt_title',
    		'choices'   => $titles,
    	#	'required'  => true,
    		'empty_value' => 'txt_title_item_empty'
    	));

		$builder->add('firstName', 'text', array(
			'label'=>'txt_firstname',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_forename',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'msg_desc_enter_forename',
			),
		));
		
		$builder->add('lastName', 'text', array(
			'label'=>'txt_lastname',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_surname',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'msg_desc_enter_surname',
			),
		));
		
		$builder->add('jobTitle', 'text', array(
			'label'=>'txt_job_title',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_job_title',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'msg_desc_enter_job_title',
			),
		));
		
		$builder->add('company', 'text', array(
			'label'=>'txt_company',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_company',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'msg_desc_enter_company_name',
			),
		));
		
		$builder->add('location', 'text', array(
			'label'=>'txt_location',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_location',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'msg_desc_enter_location',
			),
		));

		$builder->add('email', 'repeated', array(
			'type' => 'text',
		#	'required' => true,
			'invalid_message' => "msg_title_email_not_match|msg_desc_email_not_match",
			'first_name' => 'email',
			'second_name' => 'confirmEmail',
			'first_options' => array(
				'label' => 'txt_email',
				'attr' => array(
					'placeholder'			=> 'txt_email',
					'rel'					=> 'tooltip',
		#			'data-original-title'	=> 'msg_desc_enter_email',
				),
			),
		    'second_options' => array(
		    	'label' => 'txt_confirm_email',
		    	'attr' => array(
		    		'placeholder' 			=> 'txt_confirm_email',
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