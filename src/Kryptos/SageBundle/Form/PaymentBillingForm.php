<?php
namespace Kryptos\SageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Kryptos\SageBundle\Lib\CountryCodes;

class PaymentBillingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	// TODO: get Max length from config
    	
    	$builder->add('billingSurname', 'text', array(
    		'label'=>'txt_surname',
    	#	'required' => true,
    		'max_length' => 20,
    		'attr' => array(
    			'placeholder'			=> 'txt_surname',
    			'rel'					=> 'tooltip',
    	#		'data-original-title'	=> 'Please enter your Surname',
    		),
    	));
			
		$builder->add('billingFirstnames', 'text', array(
    		'label'=>'txt_firstname',
    	#	'required' => true,
    		'max_length' => 20,
			'attr' => array(
				'placeholder'			=> 'txt_firstname',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Firstname',
			),
    	));
		
		$builder->add('billingAddress1', 'text', array(
    		'label'=>'txt_address_line_1',
    	#	'required' => true,
    		'max_length' => 100,
			'attr' => array(
				'placeholder'			=> 'txt_address_line_1',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your first line of Address',
			),
    	));
		
		$builder->add('billingAddress2', 'text', array(
			'label'=>'txt_address_line_2',
		#	'required' => false,
    		'max_length' => 100,
			'attr' => array(
				'placeholder'			=> 'txt_address_line_2',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your second line of Address',
			),
		));
		
		$builder->add('billingCity', 'text', array(
			'label'=>'txt_city',
		#	'required' => true,
    		'max_length' => 40,
			'attr' => array(
				'placeholder'			=> 'txt_city',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your City',
			),
		));
		
		$builder->add('billingPostCode', 'text', array(
			'label'=>'txt_postcode',
		#	'required' => true,
    		'max_length' => 10,
			'attr' => array(
				'placeholder'			=> 'txt_postcode',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Postcode',
			),
		));
		
		
		$countryCodes = new CountryCodes();
		
		//create translation array for country list
		$choices = array();
		foreach (array_keys($countryCodes->getList()) as $countryCode) {
			$choices[$countryCode] = sprintf('txt_country_%s', $countryCode);
		}
		
		$builder->add('billingCountry', 'choice', array(
			'label'=>'txt_country',
		#	'required' => true,
    		'max_length' => 2,
			'choices' => $choices,
			'preferred_choices' => array_keys($countryCodes->getTopList()),
			'attr' => array(
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Country',
			),
		));
		
		$builder->add('acceptTerms', 'checkbox', array(
			'label'    =>'txt_accept_terms',
			'required' => true,
		));
    }
    
    
    public function getName()
    {
        return 'PaymentBillingForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\SageBundle\Entity\PaymentBilling',
			)
		);
	}	
}