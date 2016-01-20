<?php

require_once __DIR__ . '/../services/PhoneManager.php';

class Mad_Contact_IndexController extends Mage_Core_Controller_Front_Action
{
    const XML_PATH_EMAIL_RECIPIENT = 'contacts/email/recipient_email';
    const XML_PATH_EMAIL_SENDER    = 'contacts/email/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE  = 'contacts/email/email_template';
    const XML_PATH_ENABLED         = 'contacts/contacts/enabled';

    public function postAction()
    {
        $post = $this->getRequest()->getPost();

        if ($post && $this->_validateFormKey()) {
            /* @var $translate Mage_Core_Model_Translate */
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $successMessage = 'Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.';

            try {
                $error = false;

                if (!empty($post['email']) && !Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['telephone']), 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['name']), 'NotEmpty')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                if ($error) {
                    throw new InvalidFormDataException();
                }

                $this->contactToCustomer($post);

                return $this->redirectWithSuccessMessage($successMessage);
            } catch (ATSException $e) {
                return $this->redirectWithSuccessMessage($successMessage);
            } catch (InvalidPhoneNumberException $e) {
                $errorMessage = 'Your phone number is invalid. Phone format: +380XXXXXXXXX.';

                return $this->redirectWithErrorMessage($errorMessage);
            } catch (EmailSenderException $e) {
                $errorMessage = 'Unable to send email. Please, try again later.';

                return $this->redirectWithErrorMessage($errorMessage);
            } catch (InvalidFormDataException $e) {
                $errorMessage = 'Your data is invalid. Please fill form with correct data.';

                return $this->redirectWithErrorMessage($errorMessage);
            } catch (Exception $e) {
                $errorMessage = 'Unable to submit your request. Please, try again later.';

                return $this->redirectWithErrorMessage($errorMessage);
            }
        } else {
            return $this->_redirectReferer();
        }
    }

    /**
     * @param $post
     *
     * @return bool
     * @throws ATSException
     * @throws InvalidPhoneNumberException
     */
    private function contactToCustomer($post)
    {
        $phone = $post['telephone'];
        $name  = $post['name'];

        $phoneManager = new PhoneManager();
        $phoneNumber  = $phoneManager->getValidPhoneNumber($phone);
        $name         = $phoneManager->getValidName($name);
        $result       = $phoneManager->organizeCall($phoneNumber, $name);

        $email = $post['email'];

        if (!empty($email)) {
            $postObject = new Varien_Object();
            $postObject->setData($post);
            $this->sendEmail($email, $postObject);
        }

        return $result;
    }

    /**
     * @param string        $email
     * @param Varien_Object $postObject
     *
     * @throws Exception
     */
    private function sendEmail($email, Varien_Object $postObject)
    {
        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */
        $mailTemplate->setDesignConfig(['area' => 'frontend'])
                     ->setReplyTo($email)
                     ->sendTransactional(
                         Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                         Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                         Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                         null,
                         ['data' => $postObject]
                     );

        if (!$mailTemplate->getSentSuccess()) {
            throw new EmailSenderException();
        }
    }

    /**
     * @param string $successMessage
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    private function redirectWithSuccessMessage($successMessage)
    {
        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(true);

        Mage::getSingleton('customer/session')->addSuccess(
            Mage::helper('contacts')->__(
                $successMessage
            )
        );

        return $this->_redirect('/');
    }

    /**
     * @param string $errorMessage
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    private function redirectWithErrorMessage($errorMessage)
    {
        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');

        $translate->setTranslateInline(true);

        Mage::getSingleton('customer/session')->addError(
            Mage::helper('contacts')->__($errorMessage)
        );

        return $this->_redirectReferer();
    }
}

class InvalidFormDataException extends \Exception
{

}

class EmailSenderException extends \Exception
{

}
