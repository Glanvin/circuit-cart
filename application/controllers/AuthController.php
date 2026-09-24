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

    //Signs the user out and clears the session.
    public function signout()
    {
        $this->session->unset_userdata(array('username', 'role', 'email'));
        $this->toastify->success('You have been signed out.');
        redirect('auth/signin');
    }

    /*
     * Shows the sign-in form and authenticates submitted credentials, routing
     * admins to the employee dashboard and everyone else to the home page.
     */
    public function signin()
    {
        $data = [
            'title' => 'Sign In',
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
                    }elseif(password_verify($password, $response['password'])) {
                        $this->toastify->success("Welcome back {$response['username']}");
                        $this->session->set_userdata('username', $response['username']);
                        $this->session->set_userdata('role', $response['role']);
                        $this->session->set_userdata('email', $response['email']);
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

    /*
     * Shows the registration form and creates a new account, emailing a welcome
     * message to the new user before redirecting to sign-in.
     */
    public function signup()
    {
        $data = [
            'title' => 'Sign Up',
            'enable_footer' => false,
        ];
        if($this->input->post('register')) {

            $rules = $this->config->item('user_signup');
            $this->form_validation->set_rules($rules);

            if($this->form_validation->run() === true) {
                try {

                    $hashed_password = password_hash($this->input->post('password', true), PASSWORD_DEFAULT);

                    $user = [
                        'username' => strtolower($this->input->post('username', true)),
                        'firstname' => ucfirst($this->input->post('firstname', true)),
                        'middlename' => ucfirst($this->input->post('middlename', true)),
                        'lastname' => ucfirst($this->input->post('lastname', true)),
                        'contactnumber' => $this->input->post('contactnumber', true),
                        'password' => $hashed_password,
                        'email' => strtolower($this->input->post('email', true))
                    ];

                    $response = $this->UserModel->registerUser($user);
                    if($response) {
                        deliver_email(
                            'emails/welcome_email',
                            [
                                'subject' => 'Welcome to Circuit Cart',
                                'greeting' => "Dear " . ucfirst((string) $this->input->post('firstname', TRUE)) . ",",
                                'message' => 'Welcome to Circuit Cart',
                            ],
                            (string) $this->input->post('email', TRUE),
                            'Welcome to Circuit Cart'
                        );
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

    /*
     * Sends a verification code to the session email and validates the submitted
     * OTP, marking the account as verified before redirecting to settings.
     */
    public function verify()
    {
        $data = [
            'title' => 'Verify Email',
            'enable_footer' => FALSE,
        ];

        if ($this->input->post('otp')) {
            if (verify_otp((string) $this->input->post('otp', TRUE))) {
                $user = $this->UserModel->findUserByUsername($this->session->userdata('username'));
                $this->UserModel->updateUser($user['id'], ['verified_email' => 1]);

                $this->toastify->success('Your email has been verified.');
                redirect('settings', 'refresh');
            }

            $this->toastify->error('Invalid or expired verification code. Please try again.');
        } else {
            $this->ensureEmailInSession();

            $email = (string) $this->session->userdata('email');
            if ($email === '' || !store_otp($email)) {
                $this->toastify->error('Unable to send the verification code. Please try again.');
            }
        }

        $this->load->view('templates/header', $data);
        $this->load->view('auth/verify_email', $data);
    }

    //Ensures the current user's email is loaded into the session.
    private function ensureEmailInSession(): void
    {
        if (!empty($this->session->userdata('email'))) {
            return;
        }

        $username = $this->session->userdata('username');
        if (empty($username)) {
            return;
        }

        $user = $this->UserModel->findUserByUsername($username);

        if (!empty($user['email'])) {
            $this->session->set_userdata('email', $user['email']);
        }
    }
}
