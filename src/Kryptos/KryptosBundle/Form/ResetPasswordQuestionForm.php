<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordQuestionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('password', 'repeated', array(
			'type' => 'password',
		#	'required' => true,
			'invalid_message' => "Password's do not match |The Passwords you have typed do not match. Please check and try again.",
			'first_name' => 'password',
			'second_name' => 'confirmPassword',
			'first_options' => array(
				'label' => 'Password',
				'attr' => array(
					'placeholder' 			=> 'txt_password',
					'rel'					=> 'tooltip',
		#			'data-original-title'	=> 'Please enter your Password',
					'autocomplete'			=> 'off',
				),
			),
		    'second_options' => array(
		    	'label' => 'Confirm Password',
		    	'attr' => array(
		    		'placeholder' 			=> 'txt_confirm_password',
		    		'rel'					=> 'tooltip',
		#    		'data-original-title'	=> 'Please confirm your Password',
		    		'autocomplete'			=> 'off',
		    	),
		    ),
			'constraints' => array(
				new NotBlank(),
				new Length(array(
					'min' => 4, 
					'max' => 20,
					'maxMessage' => 'Invalid password|Password value is too long. It should have {{ limit }} characters or less.',
					'minMessage' => 'Invalid password|Password value is too short. It should have {{ limit }} characters or more.',
				)),
				new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'Invalid password|Password should only contain alphanumber characters. (a to z) and (0 to 9)')),
			)
		));
    }
    

    public function getName()
    {
        return 'ResetPasswordQuestionForm';
    }

	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\ResetPasswordQuestion',
			)
		);
	}
	
}