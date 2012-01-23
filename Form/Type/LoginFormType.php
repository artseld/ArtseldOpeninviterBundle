<?php

/*
 * This file is part of the Artseld\OpeninviterBundle package.
 *
 * (c) Dmitry Kozlovich <artseld@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artseld\OpeninviterBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class LoginFormType extends AbstractType
{
    protected $openinviter;

    public function __construct($openinviter)
    {
        $this->openinviter = $openinviter;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('email', 'text', array(
                'required' => true,
                'label' => 'artseld_openinviter.label.email',
            ))
            ->add('password', 'password', array(
                'required' => true,
                'label' => 'artseld_openinviter.label.password',
            ))
            ->add('provider', 'choice', array(
                'required' => true,
                'label' => 'artseld_openinviter.label.provider',
                'multiple' => false,
                'expanded' => false,
                'choices' => $this->getProviderChoices(),
            ))
        ;
    }

    public function getName()
    {
        return 'artseld_openinviter_login_form';
    }

    protected function getProviderChoices()
    {
        $choices = array();
        foreach ($this->openinviter->getPlugins() as $type => $providers)
        {
            // Email Providers, Social Networks
            $choices['artseld_openinviter.label.type_' . $type] = array();
            foreach ($providers as $provider => $details)
            {
                $choices['artseld_openinviter.label.type_' . $type][$provider] = $details['name'];
            }
        }

        return $choices;
    }
}