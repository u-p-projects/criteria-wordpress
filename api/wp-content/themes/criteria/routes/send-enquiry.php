<?php
// Handles enquiry form request sent from /enquiry/

add_action('wp_ajax_send_enquiry', 'send_enquiry');
add_action('wp_ajax_nopriv_send_enquiry', 'send_enquiry');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(TEMPLATEPATH . '/lib/PHPMailer/src/Exception.php');
require_once(TEMPLATEPATH . '/lib/PHPMailer/src/PHPMailer.php');
require_once(TEMPLATEPATH . '/lib/PHPMailer/src/SMTP.php');

function send_enquiry() {
  $data = new SendEnquiry($_POST);
  $data->init();
}

class SendEnquiry {
  public function __construct($payload) {
    $this->payload = json_decode(file_get_contents('php://input'), true);
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  private function payload() {
    $this->response = array(
      'response' => $this->send_email()
    );

    // Send customer a email about their enquiry
    $this->send_customer_email();

    echo json_encode($this->response);
    die();
  }

  private function build_product_list() {
    $list = '<tr><td><strong>Product</strong></td><td><strong>ID</strong></td><td><strong>Attributes</strong></td></tr>';
    foreach ($this->payload['enquiry']['list'] as $product) {
      $item = '<tr>';
      $item .= '<td>' . $product['product'] . '</td><td>' . $product['optionId'] . '</td>';
      $item .= '<td>';
      foreach ($product['options'] as $option) {
        $item .= $option['option'] . ': ' . $option['value'] . "<br>";
      }
      $item .= '</td>';
      $item .= '</tr>';
      $list .= $item;
    }

    return $list;
  }

  private function build_contact_info() {
    $info = "<h3>New Website Enquiry</h3></br>";
    $info .= '<p>Name: ' . $this->payload['enquiry']['name'] . '</p>';
    $info .= '<p>Email: ' . $this->payload['enquiry']['email'] . '</p>';
    $info .= '<p>Phone: ' . $this->payload['enquiry']['phone'] . '</p>';
    $info .= '<p>Project Name: ' . $this->payload['enquiry']['project'] . '</p>';
    $info .= '<p>Notes: ' . $this->payload['enquiry']['notes'] . '</p>';
    return $info;
  }

  private function build_customer_info() {
    $customer = "<h3>Criteria Product Inquiry</h3>";
    $customer .= "<br/>";
    $customer .= "<p>Hi ". $this->payload['enquiry']['name'] . ",</p>";
    $customer .= "<br/>";
    $customer .= "<p>we have recieved your enquiry about the following products:</p>";
    // $customer .= "<br/>";
    return $customer;
  }

  private function send_email() {
    $message = '<html><head><style>' . file_get_contents(TEMPLATEPATH . '/email.css', FILE_USE_INCLUDE_PATH) . '</style></head><body>'. $this->build_contact_info() . '<table>' . $this->build_product_list() . '</table>'. '</body></html>';
    $mail = new PHPMailer;

    $mailgun = $this->get_mailgun_settings();

    // Set up address info
    $SEND_ENQUIRY_EMAIL_ADDRESS = 'g@u-p.co';
    $SEND_ENQUIRY_NAME = 'Criteria Enquiry';

    try {
      // General settings
      $mail->isSMTP(); 
      $mail->SMTPDebug = 0; // 0 = off
      $mail->Debugoutput = 'html'; //Ask for HTML-friendly debug output
      $mail->Host = $mailgun['host']; //Set the hostname of the mail server - MailGun "SMTP Hostname"
      $mail->Port = 587;

      // Auth Settings
      $mail->SMTPSecure = 'tls';
      $mail->SMTPAuth = true;
      $mail->Username = $mailgun['username'];
      $mail->Password = $mailgun['password'];
    
      $mail->setFrom($this->payload['enquiry']['email'], $this->payload['enquiry']['name']);
      $mail->addAddress($SEND_ENQUIRY_EMAIL_ADDRESS, $SEND_ENQUIRY_NAME);
      $mail->addReplyTo($this->payload['enquiry']['email'], $this->payload['enquiry']['name']);
      $mail->isHTML(true);
      $mail->Subject = 'Website Enquiry (Criteria Collection)';
      $mail->Body = $message;
      $mail->AltBody = $message;
      $mail->send();
      $response = array(
        'sent' => true,
        'message' => 'Your enquiry has been sent.' . "\n" . 'We will be in touch with you shortly.'
      );
    } catch (Exception $e) {
      $response = array(
        'sent' => false,
        'message' => 'Enquiry could not be sent.' . "\n" . 'Error: ' . $mail->ErrorInfo
      );
    }
    return $response;
  }

  private function send_customer_email() {
    $message = '<html><head><style>';
    $message .= file_get_contents(TEMPLATEPATH . '/email.css', FILE_USE_INCLUDE_PATH) . '</style></head>';
    $message .= "<body>";
    $message .= $this->build_customer_info();
    $message .= '<table>' . $this->build_product_list() . '</table>';
    $message .=  "<br/><br/>";
    $message .=  "<p>Someone from the Criteria team will be back to you shortly.</p>";
    $message .=  "<br/>";
    $message .=  "<p>Cheers</p>";
    $message .= '</body></html>';
    
    $mail = new PHPMailer;

    $mailgun = $this->get_mailgun_settings();

    // Set up address info
    $SEND_ENQUIRY_EMAIL_ADDRESS = 'g@u-p.co';
    $SEND_ENQUIRY_NAME = 'Criteria Enquiry';

    try {
      // General settings
      $mail->isSMTP(); 
      $mail->SMTPDebug = 0; // 0 = off
      $mail->Debugoutput = 'html'; //Ask for HTML-friendly debug output
      $mail->Host = $mailgun['host']; //Set the hostname of the mail server - MailGun "SMTP Hostname"
      $mail->Port = 587;

      // Auth Settings
      $mail->SMTPSecure = 'tls';
      $mail->SMTPAuth = true;
      $mail->Username = $mailgun['username'];
      $mail->Password = $mailgun['password'];
    
      $mail->setFrom($SEND_ENQUIRY_EMAIL_ADDRESS, $SEND_ENQUIRY_NAME);
      $mail->addAddress($this->payload['enquiry']['email'], $this->payload['enquiry']['name']);
      $mail->addReplyTo($this->payload['enquiry']['email'], $this->payload['enquiry']['name']);
      $mail->isHTML(true);
      $mail->Subject = 'Criteria Product Enquiry';
      $mail->Body = $message;
      $mail->AltBody = $message;
      $mail->send();

      // Todo add valid response, handle success
      
    } catch (Exception $e) {
      // Todo add exception handling, handle error
    }

  }

  private function get_mailgun_settings() {

    return array(
      'host' => 'smtp.mailgun.org',
      'username' => 'postmaster@sandbox761999cb206b4b53a3493a63648a328a.mailgun.org',
      'password' => 'upemail'
    );
  }
}
?>
