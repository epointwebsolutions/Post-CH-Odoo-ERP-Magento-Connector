<?php
/**
 * Helper
 *
 */
class Epoint_SwissPost_Api_Helper_MailLog extends Mage_Core_Helper_Abstract {
	
	  /**
   * Setting path for service entry point
   */
  const XML_CONFIG_PATH_ENABLE_EMAIL_LOG = 'swisspost_api/log/enable_emails';
  const XML_CONFIG_PATH_EMAILS = 'swisspost_api/log/emails';
	
    /**
     * custom log method
     *
     * @param $result object
     */
    function sendAPIResult(ApiResult $result){
    	if(!Mage::getStoreConfig(self::XML_CONFIG_PATH_ENABLE_EMAIL_LOG)){
    		return ;
    	}
    	$message[] = $this->__('SWISSPOST API call');
    	$message[] = $this->__('URL: %s', Mage::helper('core/url')->getCurrentUrl());
    	if($result->getError()){
    		$message[] = $this->__('ERROR SWISSPOST API call: %s', print_r($result->getError(), 1));
    	}
    	$message[] = $this->__('DEBUG SWISSPOST API call: %s', print_r($result->getDebug(), 1));
        // send mail
        $emails = explode(',', Mage::getStoreConfig(self::XML_CONFIG_PATH_EMAILS));
        if($emails){
            // build a raw ouput
            ob_start();
            print implode("\n", $message);
            $mail_message = ob_get_clean();
            foreach ($emails as $email){
                self::sendmail($email, 'Epoint SwissPost API message', $mail_message, Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'));
            }
        }
    }
    /**
     * Log exception on email
     *
     * @param Exception $exception
     */
    function sendException(Exception $exception){
      if(!Mage::getStoreConfig(self::XML_CONFIG_PATH_ENABLE_EMAIL_LOG)){
    	return ;
      }
      $message = $this->__('EPoint SwissPost API Exception: %s, line: %s, file: %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
      // send mail
      $emails = explode(',', Mage::getStoreConfig('watchdog/settings/emails'));
      if($emails){
            // build a raw ouput
            ob_start();
            print 'MESSAGE: '.$message."\n";
            print 'URL: '.Mage::helper('core/url')->getCurrentUrl()."\n";
            $mail_message = ob_get_clean();
            foreach ($emails as $email){
            	if($email){
              		self::sendmail($email, 'Epoint SwissPost API Exception', $mail_message, Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'));
            	}
            }
        }
    }
    /**
     * send mail
     *
     * @param email $to
     * @param subject $subject
     * @param unknown_type $body
     * @return unknown
     */
    public function sendmail($toEmail, $subject, $body, $fromEmail, $fromName) {
        try{
        	
            $mail = new Zend_Mail('UTF-8');
            $mail->addTo($toEmail)
                ->setFrom($fromEmail, $fromName)
                ->setSubject($subject)
                ->setBodyText($body, null, Zend_Mime::ENCODING_8BIT);
            $mail->send();
            $content = ob_get_clean();
            return TRUE;
        }
        catch (Exception $e) {
            Mage::log('Error on send email from maillogger:'.$toEmail);
            return FALSE;
        }
    }
	
    
}
