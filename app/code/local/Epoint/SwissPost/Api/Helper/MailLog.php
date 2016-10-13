<?php

/**
 * Helper
 *
 */
class Epoint_SwissPost_Api_Helper_MailLog extends Mage_Core_Helper_Abstract
{

  /**
   * Setting path for service entry point
   */
  const XML_CONFIG_PATH_ENABLE_EMAIL_LOG = 'swisspost_api/log/enable_emails';
  const XML_CONFIG_PATH_EMAILS = 'swisspost_api/log/emails';

  /**
   * Custom log method
   *
   * @param ApiResult $result
   */
  public function sendAPIResult(ApiResult $result)
  {
    if (!Mage::getStoreConfig(self::XML_CONFIG_PATH_ENABLE_EMAIL_LOG)) {
      return;
    }
    $message[] = $this->__('SWISSPOST API call');
    $message[] = $this->__('URL: %s', Mage::helper('core/url')->getCurrentUrl());
    if ($result->getError()) {
      $message[] = $this->__('ERROR SWISSPOST API call: %s', print_r($result->getError(), 1));
    }
    $message[] = $this->__('DEBUG SWISSPOST API call: %s', print_r($result->getDebug(), 1));
    // send mail
    $emails = explode(',', Mage::getStoreConfig(self::XML_CONFIG_PATH_EMAILS));
    if ($emails) {
      // build a raw ouput
      ob_start();
      print implode("\n", $message);
      $mail_message = ob_get_clean();
      foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
          self::sendmail(
            $email, 'Epoint SwissPost API message', $mail_message
          );
        }
      }
    }
  }

  /**
   * Log exception on email
   *
   * @param Exception $exception
   */
  public function sendException(Exception $exception)
  {
    if (!Mage::getStoreConfig(self::XML_CONFIG_PATH_ENABLE_EMAIL_LOG)) {
      return;
    }
    $message = $this->__(
      'EPoint SwissPost API Exception: %s, line: %s, file: %s', $exception->getMessage(), $exception->getLine(),
      $exception->getFile()
    );
    // send mail
    $emails = explode(',', Mage::getStoreConfig('watchdog/settings/emails'));
    if ($emails) {
      // build a raw ouput
      ob_start();
      print 'MESSAGE: ' . $message . "\n";
      print 'URL: ' . Mage::helper('core/url')->getCurrentUrl() . "\n";
      $mail_message = ob_get_clean();
      foreach ($emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
          self::sendmail(
            $email, 'Epoint SwissPost API Exception', $mail_message,
            Mage::getStoreConfig('trans_email/ident_general/email'),
            Mage::getStoreConfig('trans_email/ident_general/name')
          );
        }
      }
    }
  }

  /**
   * Send mail
   *
   * @param $toEmail
   * @param $subject
   * @param $body
   * @param $fromEmail
   * @param $fromName
   *
   * @return bool
   */
  public static function sendmail($toEmail, $subject, $body, $fromEmail = '', $fromName = '')
  {
    try {
      if(!$fromEmail){
        $fromEmail = Mage::getStoreConfig('trans_email/ident_general/email');
      }
      if(!$fromName){
        $fromName = Mage::getStoreConfig('trans_email/ident_general/name');
      }
      $mail = new Zend_Mail('UTF-8');
      $mail->addTo($toEmail)
        ->setFrom($fromEmail, $fromName)
        ->setSubject($subject)
        ->setBodyText($body, null, Zend_Mime::ENCODING_8BIT);
      $mail->send();
      $content = ob_get_clean();

      return true;
    } catch (Exception $e) {
      Mage::log('Error on send email from maillogger:' . $toEmail);

      return false;
    }
  }


}
