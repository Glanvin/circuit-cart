<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailController extends CI_Controller {

    public function send() {
        $this->load->library('email');

        $this->email->from('valmores.sethmatthew@gmail.com', 'Circuit Cart');
        $this->email->to('seth.valmy@gmail.com');
        $this->email->subject('Testing Mailjet SMTP 2');
        $this->email->message('<h1>Hellow</h1>');

        if ($this->email->send()) {
            echo 'Email sent successfully.';
        } else {
            // Display errors if it fails
            echo $this->email->print_debugger();
        }
    }
}