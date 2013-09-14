<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SigninForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('email', 'text', array(
    		'label'=>'Email',
    	#	'required' => true,
    		'attr' => array(
    			'placeholder'			=> 'txt_email',
    			'rel'					=> 'tooltip',
    	#		'data-original-title'	=> 'Please enter your Email.',
    		),
    	));
			
		$builder->add('password', 'password', array(
			'label' => 'Password',
		#	'required' => true,
			'attr' => array(
				'placeholder'			=> 'txt_password',
				'rel'					=> 'tooltip',
		#		'data-original-title'	=> 'Please enter your Account Password.',
			),
		));
		
		
		/*
		$builder->add('staySignedIn', 'checkbox', array(
			'label' => 'Stay signed in',
			'required' => false,
		));
		*/
		
		#$builder->add('key', 'csrf');
    }
    

    public function getName()
    {
        return 'SigninForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\Signin',
			)
		);
	}
	
}