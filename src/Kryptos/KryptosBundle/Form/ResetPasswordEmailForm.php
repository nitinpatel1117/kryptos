<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordEmailForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('email', 'email', array(
    		'label'=>'Email',
    	#	'required' => true,
    		'attr' => array(
    			'placeholder'			=> 'txt_email',
    			'rel'					=> 'tooltip',
    	#		'data-original-title'	=> 'Please enter your Email.',
    		),
    	));
		
		#$builder->add('key', 'csrf');
    }
    

    public function getName()
    {
        return 'ResetPasswordEmailForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\ResetPasswordEmail',
			)
		);
	}
	
}