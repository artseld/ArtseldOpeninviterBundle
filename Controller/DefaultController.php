<?php

/*
 * This file is part of the Artseld\OpeninviterBundle package.
 *
 * (c) Dmitry Kozlovich <artseld@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artseld\OpeninviterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Artseld\OpeninviterBundle\Form\Type\LoginFormType;
use Artseld\OpeninviterBundle\Form\Type\InviteFormType;

use Artseld\OpeninviterBundle\ArtseldOpeninviter\ArtseldOpeninviter;

class DefaultController extends Controller
{
    // Steps
    const STEP_LOGIN    = 'login';
    const STEP_INVITE   = 'invite';
    const STEP_DONE     = 'done';

    // Session variables
    const SVAR_STEP         = 'step';
    const SVAR_SESSID       = 'sessid';
    const SVAR_PROVIDER     = 'provider';
    const SVAR_EMAIL        = 'email';
    const SVAR_CONTACTS     = 'contacts';

    // Flash types
    const FLASH_SUCCESS     = 'success';
    const FLASH_ERROR       = 'error';

    protected $openinviter;
    protected $oiPlugins;

    /**
     * Login action
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function loginAction(Request $request)
    {
        $this->_init();

        if ($this->_getSessionVar(self::SVAR_STEP) != self::STEP_LOGIN) {
            $this->_clearSessionVar();
            $this->_setSessionVar(self::SVAR_STEP, self::STEP_LOGIN);
        }
        $form = $this->get('form.factory')->create(new LoginFormType( $this->openinviter ));
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $values = $form->getData();
                $this->openinviter->startPlugin($values['provider']);
                $internal = $this->openinviter->getInternalError();
                if ($internal) {
                    $form->addError(new \Symfony\Component\Form\FormError( $this->_trans($internal) ));
                } elseif (!$this->openinviter->login( $values['email'], $values['password'] )) {
                    $internal = $this->openinviter->getInternalError();
                    $form->addError(new \Symfony\Component\Form\FormError( $this->_trans(
                        $internal ? $internal : 'artseld_openinviter.notification.error.incorrect_login'
                    )));
                } elseif (false === $contacts = $this->openinviter->getMyContacts()) {
                    $form->addError(new \Symfony\Component\Form\FormError(
                        $this->_trans('artseld_openinviter.notification.error.cannot_get_contacts')
                    ));
                } else {
                    $this->_setSessionVar(array(
                        self::SVAR_STEP     => self::STEP_INVITE,
                        self::SVAR_SESSID   => $this->openinviter->plugin->getSessionID(),
                        self::SVAR_PROVIDER => $values['provider'],
                        self::SVAR_EMAIL    => $values['email'],
                        self::SVAR_CONTACTS => $contacts,
                    ));
                    return new RedirectResponse($this->generateUrl('artseld_openinviter_invite'));
                }
            }
        }

        return $this->get('templating')->renderResponse(
            'ArtseldOpeninviterBundle:Default:login.html.twig', array(
                'login_form' => $form->createView(),
            ));
    }

    /**
     * Invite action
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function inviteAction(Request $request)
    {
        $this->_init();

        if ($this->_getSessionVar(self::SVAR_STEP) != self::STEP_INVITE) {
            return new RedirectResponse($this->generateUrl('artseld_openinviter_login'));
        }

        $form = $this->get('form.factory')->create(new InviteFormType( $this->_getSessionVar(self::SVAR_CONTACTS) ));
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $values = $form->getData();
                $this->openinviter->startPlugin( $this->_getSessionVar(self::SVAR_PROVIDER) );
                $internal = $this->openinviter->getInternalError();
                if ($internal) {
                    $form->addError(new \Symfony\Component\Form\FormError( $this->_trans($internal) ));
                } else {
                    if (empty($values['email'])) {
                        $form->addError(new \Symfony\Component\Form\FormError(
                            $this->_trans('artseld_openinviter.notification.error.email_not_set')
                        ));
                    }
                    $sessid = $this->_getSessionVar(self::SVAR_SESSID);
                    if (empty($sessid)) {
                        $form->addError(new \Symfony\Component\Form\FormError(
                            $this->_trans('artseld_openinviter.notification.error.no_active_session')
                        ));
                    }
                    if (empty($values['message'])) {
                        $form->addError(new \Symfony\Component\Form\FormError(
                            $this->_trans('artseld_openinviter.notification.error.message_missing')
                        ));
                    } else {
                        $values['message'] = strip_tags($values['message']);
                    }
                    $message = array(
                        'subject'       => $this->_trans('artseld_openinviter.text.message_subject',
                            array('%link%' => $this->generateUrl('_welcome', array(), true))),
                        'body'          => $this->_trans('artseld_openinviter.text.message_body',
                            array('%username%' => $this->_getSessionVar(self::SVAR_EMAIL),
                                '%link%' => $this->generateUrl('_welcome', array(), true))) . "\n\r" . $values['message'],
                        'attachment'    => '',
                    );
                    $selectedContacts = array();
                    if ($this->openinviter->showContacts())
                    {
                        $i = 0;
                        foreach ($this->_getSessionVar(self::SVAR_CONTACTS) as $email => $name) {
                            if (in_array($i, $values['email'])) $selectedContacts[$email] = $name;
                            $i++;
                        }
                        if (count($selectedContacts) == 0) {
                            $form->addError(new \Symfony\Component\Form\FormError(
                                $this->_trans('artseld_openinviter.notification.error.contacts_not_selected')
                            ));
                        }
                    }
                }
                if (count($form->getErrors()) == 0) {
                    $sendMessage = $this->openinviter->sendMessage(
                        $this->_getSessionVar(self::SVAR_SESSID), $message, $selectedContacts);
                    $this->openinviter->logout();
                    if ($sendMessage === -1) {
                        foreach ($selectedContacts as $email => $name) {
                            $mail = \Swift_Message::newInstance()
                                ->setSubject($message['subject'])
                                ->setFrom($this->_getSessionVar(self::SVAR_EMAIL))
                                ->setTo(array($email => $name))
                                ->setBody($message['body'] . $message['attachment']);
                            $this->container->get('mailer')->send($mail);
                        }
                        $this->_setFlash(self::FLASH_SUCCESS, 'artseld_openinviter.notification.success.invitations_sent');
                    } elseif ($sendMessage === false) {
                        $internal = $this->openinviter->getInternalError();
                        $this->_setFlash(self::FLASH_ERROR, $internal ? $internal
                            : 'artseld_openinviter.notification.error.invitations_with_errors'
                        );
                    } else {
                        $this->_setFlash(self::FLASH_SUCCESS, 'artseld_openinviter.notification.success.invitations_sent');
                    }
                    return new RedirectResponse($this->generateUrl('artseld_openinviter_done'));
                }
            }
        }

        return $this->get('templating')->renderResponse(
            'ArtseldOpeninviterBundle:Default:invite.html.twig', array(
                'invite_form' => $form->createView(),
            ));
    }

    /**
     * Done action
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function doneAction()
    {
        $this->_init();

        $this->_clearSessionVar();

        return $this->get('templating')->renderResponse(
            'ArtseldOpeninviterBundle:Default:done.html.twig', array(
            ));
    }

    /**
     * Create Openinviter instance and load plugins
     */
    protected function _init()
    {
        $this->openinviter = new ArtseldOpeninviter( $this->container );
        $this->oiPlugins = $this->openinviter->getPlugins();
    }

    /**
     * Set session variable
     * @param $name
     * @param $value
     * @return DefaultController
     */
    protected function _setSessionVar($name, $value = null)
    {
        $this->_checkSessionVar($name);
        if (is_array($name) && null === $value) {
            foreach ($name as $k => $v) {
                $this->get('session')->set('artseld_openinviter.session.' . $k, $v);
            }
        } else {
            $this->get('session')->set('artseld_openinviter.session.' . $name, $value);
        }

        return $this;
    }

    protected function _getSessionVar($name) {
        $this->_checkSessionVar($name);
        return $this->get('session')->get('artseld_openinviter.session.' . $name);
    }

    /**
     * Clear session variable
     * @param $name
     * @return DefaultController
     */
    protected function _clearSessionVar($name = null)
    {
        if (null !== $name) {
            $this->_checkSessionVar($name);
            if (is_array($name)) {
                foreach ($name as $item) {
                    $this->_setSessionVar($item, null);
                }
            } else {
                $this->_setSessionVar($name, null);
            }
        } else {
            foreach ($this->_getAvailableSessionVars() as $sessionVar) {
                $this->_setSessionVar($sessionVar, null);
            }
        }

        return $this;
    }

    /**
     * Check if valid session variable name called
     * @param $name
     * @return bool
     * @throws \RuntimeException
     */
    protected function _checkSessionVar($name)
    {
        $checked = true;
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (is_numeric($k)) {
                    $item = $v;
                } else {
                    $item = $k;
                }
                if (!in_array($item, $this->_getAvailableSessionVars())) {
                    $checked = false;
                }
            }
        } else {
            if (!in_array($name, $this->_getAvailableSessionVars())) {
                $checked = false;
            }
        }
        if (!$checked) {
            throw new \RuntimeException('Incorrect session variable called', 500);
        }

        return $checked;
    }

    /**
     * Get all available session variables as array list
     * @return array
     */
    protected function _getAvailableSessionVars()
    {
        $reflection = new \ReflectionClass($this);
        $sessionVars = array();

        foreach ($reflection->getConstants() as $k => $v) {
            if (substr($k, 0, 5) === 'SVAR_') $sessionVars[$k] = $v;
        }

        return $sessionVars;
    }

    /**
     * Set flash message
     * @param $type
     * @param $message
     * @return DefaultController
     */
    protected function _setFlash( $type, $message )
    {
        if (!in_array($type, array(self::FLASH_SUCCESS, self::FLASH_ERROR))) {
            $type = self::FLASH_ERROR;
        }
        $this->get('session')->setFlash('artseld_openinviter.notification.' . $type, $message);

        return $this;
    }

    /**
     * Translate message
     * @param $message
     * @return mixed
     */
    protected function _trans( $message, $params = array() )
    {
        return $this->get('translator')->trans($message, $params);
    }

}
