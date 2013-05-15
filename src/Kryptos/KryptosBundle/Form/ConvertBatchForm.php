<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConvertBatchForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('attachment', 'file', array(
    		'label'=>'Upload Source File',
    		'required' => true,
    		'constraints' => array(
    			new NotBlank(),
    		)
    	));
    }
    

    public function getName()
    {
        return 'ConvertBatchForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		/*
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\ConvertBatch',
			)
		);
		*/
	}
	
}