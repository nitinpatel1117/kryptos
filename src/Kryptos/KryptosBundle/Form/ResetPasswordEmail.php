<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ResetPasswordEmailForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('email', 		 'text', array(
    		'label'=>'Email',
    		'required' => true
    	));
		
		#$builder->add('key', 'csrf');
    }
    

    public function getName()
    {
        return 'ResetPasswordForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\ResetPasswordEmail',
			)
		);
	}
	
}