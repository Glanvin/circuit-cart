<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthController extends CI_Controller {

    //Entry point for authentication.
    public function index()
    {
        if ($this->session->userdata('username')) {
            redirect($this->session->userdata('role') === 'admin' ? 'employee/dashboard' : 'home');
        }

        $this->signin();
    }

    public function signout()
    {
        $this->session->unset_userdata(array('username', 'role'));
        $this->toastify->success('You have been signed out.');
        redirect('auth/signin');
    }

    public function signin()
    {
        $data = [
            'title'         => 'Sign In',
            'enable_footer' => false,
        ];

        if($this->input->post('signin')) {

            $rules = $this->config->item('user_signin');
            $this->form_validation->set_rules($rules);

            if($this->form_validation->run() === true) {
                try {
                    $username = $this->input->post('username', true);
                    $password = $this->input->post('password', true);

                    $response = $this->UserModel->signInUser($username);

                    if(!$response) {
                        $this->toastify->error("User does not exist");
                    }elseif($response['password'] === $password) {
                        $this->toastify->success("Welcome back {$response['username']}");
                        $this->session->set_userdata('username', $response['username']);
                        $this->session->set_userdata('role', $response['role']);
                        redirect($response['role'] === 'admin' ? 'employee/dashboard' : 'home');
                    }else {
                        $this->toastify->error("Invalid credentials passed");
                    }
                }catch(AppException $e) {
                    $this->toastify->error($e->toToast());
                    $this->signin();
                }
            }else {
                $this->toastify->error('Please correct the highlighted fields');
            }
        }

        $this->load->view('templates/header', $data);
        $this->load->view('auth/sign_in', $data);
    }

    public function signup()
    {
        $data = [
            'title'         => 'Sign Up',
            'enable_footer' => false,
        ];
        if($this->input->post('register')) {

            $rules = $this->config->item('user_signup');
            $this->form_validation->set_rules($rules);

            if($this->form_validation->run() === true) {
                try {
                    $user = [
                        'username' => strtolower($this->input->post('username', true)),
                        'firstname' => ucfirst($this->input->post('firstname', true)),
                        'middlename' => ucfirst($this->input->post('middlename', true)),
                        'lastname' => ucfirst($this->input->post('lastname', true)),
                        'contactnumber' => $this->input->post('contactnumber', true),
                        'password' => $this->input->post('password', true),
                        'email' => strtolower($this->input->post('email', true))
                    ];

                    $response = $this->UserModel->registerUser($user);
                    if($response) {
                        $this->toastify->success("Registration successul");
                        redirect('auth/signin');
                    }else {
                        $this->toastify->error("Registration unsuccessful");
                        redirect('auth/signup');
                    }
                    $this->load->view('templates/header', $data);
                    $this->load->view('auth/sign_up', $data);
                    return;

                }catch(AppException $e) {
                    $this->toastify->error($e->toToast());
                    $this->load->view('templates/header', $data);
                    $this->load->view('auth/sign_up', $data);
                    return;
                }
            }else {
                $this->toastify->error('Please correct the highlighted fields');
                $this->load->view('templates/header', $data);
                $this->load->view('auth/sign_up', $data);
                return;
            }
        }

        $this->load->view('templates/header', $data);
        $this->load->view('auth/sign_up', $data);
    }
}
