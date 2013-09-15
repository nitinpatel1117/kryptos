<?php
namespace Kryptos\KryptosBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class SettingsUserPasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {    	
    	$builder->add('oldPassword', 'password', array(
    		'label'			=>'txt_old_password',
    	#	'required'  	=> true,
    		'attr' => array(
    			'placeholder'			=> 'txt_old_password',
    			'rel'					=> 'tooltip',
    	#		'data-original-title'	=> 'Please enter your old Password',
    			'autocomplete'	=> 'off',
    		),
    		'constraints' 	=> array(
    			new NotBlank(),
    			new Length(array(
    				'min' => 4,
    				'max' => 20,
    				'maxMessage' => 'msg_title_invalid_password|msg_desc_password_too_long',
    				'minMessage' => 'msg_title_invalid_password|msg_desc_password_too_short',
    			)),
    			new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'msg_title_invalid_password|msg_desc_password_only_alphanum')),
    		)
    	));
    	
    	
    	$builder->add('password', 'repeated', array(
    		'type' => 'password',
    	#	'required' => true,
    		'invalid_message' => "msg_title_password_not_match|msg_desc_new_passwords_not_match",
    		'first_name' => 'password',
    		'second_name' => 'confirmPassword',
    		'first_options' => array(
    			'label' => 'txt_password',
    			'attr' => array(
    				'placeholder' 			=> 'txt_new_password',
    				'rel'					=> 'tooltip',
    	#			'data-original-title'	=> 'Please enter your new Password',
    				'autocomplete'			=> 'off',
    			),
    		),
    		'second_options' => array(
    			'label' => 'txt_confirm_password',
    			'attr' => array(
    				'placeholder' 			=> 'txt_confirm_new_password',
    				'rel'					=> 'tooltip',
    	#			'data-original-title'	=> 'Please confirm your new Password',
    				'autocomplete'			=> 'off',
    			),
    		),
    		'constraints' => array(
    			new NotBlank(),
    			new Length(array(
    				'min' => 4,
    				'max' => 20,
    				'maxMessage' => 'msg_title_invalid_password|msg_desc_password_too_long',
    				'minMessage' => 'msg_title_invalid_password|msg_desc_password_too_short',
    			)),
    			new Regex(array('pattern' => '/^[a-z0-9]+$/i', 'message' => 'msg_title_invalid_password|msg_desc_password_only_alphanum')),
    		)
    	));
    }


    public function getName()
    {
        return 'SettingsUserPasswordForm';
    }


	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
    	$resolver->setDefaults(array(
			'data_class' => 'Kryptos\KryptosBundle\Entity\SettingsUserPassword',
			)
		);
	}
}