<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Kryptos\KryptosBundle\Lib\BbanCountryMappings\Mappings;

class ConvertSingleForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$mappings = new Mappings();
    	$countries = $mappings->getCountries();
    	
    	$builder->add('country', 'choice', array(
    		'choices'   => $countries,
    		'required'  => true,
    		'empty_value' => 'Choose a country',
    	));
    	
    	// determine required fields
    	$required_bban1 = false;
    	$required_bban2 = false;
    	$required_bban3 = false;
    	$required_bban4 = false;
    	$required_bban5 = false;
    	
    	// determine input labels
    	$label_bban1 = 'BBAN 1';
    	$label_bban2 = 'BBAN 2';
    	$label_bban3 = 'BBAN 3';
    	$label_bban4 = 'BBAN 4';
    	$label_bban5 = 'BBAN 5';
    	 
    	if (isset($options['country']) && !is_null($options['country'])) {
    		$bbanMaps = $mappings->getBbanMappings($options['country']);
    		if (is_array($bbanMaps)) {
	    		foreach ($bbanMaps as $key => $value) {
	    			$requiredName 	= 'required_'.$key;
	    			$labelName 		= 'label_'.$key;
	    			
	    			$$requiredName = true;
	    			$$labelName = $value;
	    		}
    		}
    	}
    	
    	
    	$builder->add('bban1', 'text', array(
    		'label' => $label_bban1,
    		'required' => $required_bban1,
    		'attr' => array(
    			'placeholder'			=> 'BBAN1',
    			'rel'					=> 'poppver',
    			'data-original-title'	=> 'Please enter your bban1.',
    		),
    	));
    	
    	$builder->add('bban2', 'text', array(
    		'label' => $label_bban2,
    		'required' => $required_bban2,
    		'attr' => array(
    			'placeholder'			=> 'BBAN2',
    			'rel'					=> 'poppver',
    			'data-original-title'	=> 'Please enter your bban2.',
    		),
    	));
    	
    	$builder->add('bban3', 'text', array(
    		'label' => $label_bban3,
    		'required' => $required_bban3,
    		'attr' => array(
    			'placeholder'			=> 'BBAN3',
    			'rel'					=> 'poppver',
    			'data-original-title'	=> 'Please enter your bban3.',
    		),
    	));
    	
    	$builder->add('bban4', 'text', array(
    		'label' => $label_bban4,
    		'required' => $required_bban4,
    		'attr' => array(
    			'placeholder'			=> 'BBAN4',
    			'rel'					=> 'poppver',
    			'data-original-title'	=> 'Please enter your bban4.',
    		),
    	));
    	
    	$builder->add('bban5', 'text', array(
    		'label' => $label_bban5,
    		'required' => $required_bban5,
    		'attr' => array(
    			'placeholder'			=> 'BBAN5',
    			'rel'					=> 'poppver',
    			'data-original-title'	=> 'Please enter your bban5.',
    		),
    	));
    }
    

    public function getName()
    {
        return 'ConvertSingleForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\ConvertSingle',
    		'country' => '',
			)
		);
		
	}
	
}