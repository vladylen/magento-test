<?php

class Mad_Contact_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        var_dump(123);
        //Get current layout state
        $this->loadLayout();

        $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'mad.contact',
            array(
                'template' => 'mad/contact.phtml'
            )
        );

        $this->getLayout()->getBlock('content')->append($block);
        //$this->getLayout()->getBlock('right')->insert($block, 'catalog.compare.sidebar', true);

        $this->_initLayoutMessages('core/session');

        $this->renderLayout();
    }

    public function sendemailAction()
    {
        //Fetch submited params
        $params = $this->getRequest()->getParams();

        $mail = new Zend_Mail();
        $mail->setBodyText($params['comment']);
        $mail->setFrom($params['email'], $params['name']);
        $mail->addTo('somebody_else@example.com', 'Some Recipient');
        $mail->setSubject('Test Mad_Contact Module for Magento');
        try {
            $mail->send();
        }
        catch(Exception $ex) {
            Mage::getSingleton('core/session')->addError('Unable to send email. Sample of a custom notification error from Mad_Contact.');

        }

        //Redirect back to index action of (this) mad-contact controller
        $this->_redirect('contact/');
    }
}
