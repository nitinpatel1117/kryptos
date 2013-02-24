<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseConversionsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('conversions', 'text', array(
    		'label'=>'No. Conversions',
    		'required' => true
    	));
			
		$builder->add('cost', 'text', array(
			'label' => 'Total Cost',
			'required' => false,
			'disabled' => 'true',
		));
		
		
		$builder->add('vat', 'text', array(
			'label' => 'Total VAT',
			'required' => false,
			'disabled' => 'true',
		));
    }
    

    public function getName()
    {
        return 'PurchaseConversionsForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\PurchaseConversions',
			)
		);
	}
	
}