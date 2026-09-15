<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailController extends CI_Controller {


    public function index()
    {
        $data = [
            'subject' => 'Welcome to Circuit Cart',
            'openning_message' => "Dear Kyla Tagam,",
            'main_message' => "We Hope you enjoy our site.",
        ];

        $this->load->view('emails/welcome_email', $data);
    }

    public function send() 
    {
        $this->load->library('session');

        // $last = $this->session->userdata('last_mail_ts');
        // if ($last !== NULL && (time() - $last) < 60) {
        //     echo "Please wait before sending again (Gmail rate-limit guard).";
        //     return;
        // }
        // $this->session->set_userdata('last_mail_ts', time());

        $data = [
            'subject' => 'Welcome to Circuit Cart',
            'openning_message' => "Dear Kyla Tagam,",
            'main_message' => "We Hope you enjoy our site.",
        ];

        $message = $this->load->view('emails/welcome_email.php', $data, true);

        $this->email->from('2092437@g.cu.edu.ph', 'Circuit Cart');
        $this->email->to('2091908@g.cu.edu.ph');

        $this->email->subject($data['subject']);

        $this->email->message($message);

        if($this->email->send()) {
            echo "Email Sent!";
        }else {
            echo "SMTP Error Details:<br>";
            echo $this->email->print_debugger();
        }

    }
}
