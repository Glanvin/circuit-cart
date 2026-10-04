<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ErrorController extends CI_Controller {

    //Renders the 404 error page for missing routes.
    public function index()
    {
        $this->output->set_status_header(404);

        $data = [
            'title' => 'Not Found 404',
            'enable_footer' => true,
        ];

        $this->load->view('templates/header', $data);

        if ($this->session->userdata('employee_id')) {
            $this->load->view('templates/navbar_employees', $data);
        } elseif ($this->session->userdata('username')) {
            $this->load->view('templates/navbar', $data);
        }

        $this->load->view('errors/custom/error_404', $data);
        $this->load->view('templates/footer', $data);
    }
}