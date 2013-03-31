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
    		'label'=>'Surname',
    		'required' => true,
    		'max_length' => 20,
    	));
			
		$builder->add('billingFirstnames', 'text', array(
    		'label'=>'Firstname',
    		'required' => true,
    		'max_length' => 20,
    	));
		
		$builder->add('billingAddress1', 'text', array(
    		'label'=>'Address line 1',
    		'required' => true,
    		'max_length' => 100,
    	));
		
		$builder->add('billingAddress2', 'text', array(
			'label'=>'Address line 2',
			'required' => false,
    		'max_length' => 100,
		));
		
		$builder->add('billingCity', 'text', array(
			'label'=>'City',
			'required' => true,
    		'max_length' => 40,
		));
		
		$builder->add('billingPostCode', 'text', array(
			'label'=>'Postcode',
			'required' => true,
    		'max_length' => 10,
		));
		
		$countryCodes = new CountryCodes();
		$builder->add('billingCountry', 'choice', array(
			'label'=>'Country',
			'required' => true,
    		'max_length' => 2,
			'choices' => $countryCodes->getList(),
			'preferred_choices' => array_keys($countryCodes->getTopList()),
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