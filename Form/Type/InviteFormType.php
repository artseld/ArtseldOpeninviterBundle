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

class InviteFormType extends AbstractType
{
    protected $contacts;

    public function __construct($contacts = array())
    {
        $this->contacts = $contacts;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('message', 'textarea', array(
                'required' => true,
                'label' => 'artseld_openinviter.label.message',
            ))
            ->add('email', 'choice', array(
                'required' => true,
                'label' => 'artseld_openinviter.label.recipients',
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->getRecipientsChoices(),
            ))
        ;
    }

    public function getName()
    {
        return 'artseld_openinviter_invite_form';
    }

    protected function getRecipientsChoices()
    {
        $choices = array();
        foreach ($this->contacts as $email => $name)
        {
            $choices[] = $email . ' | ' . $name;
        }

        return $choices;
    }
}