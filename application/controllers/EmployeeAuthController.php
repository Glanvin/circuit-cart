<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmployeeAuthController extends CI_Controller {

    //Entry point: routes an already signed-in employee to their role home,
    //otherwise shows the employee sign-in page.
    public function index()
    {
        if ($this->session->userdata('employee_id')) {
            redirect($this->roleHome(), 'refresh');
        }

        $this->signin();
    }

    //The landing page per the sign-in employee's role (config/roles.php).
    private function roleHome(): string
    {
        $role = (string) $this->session->userdata('role');
        $homes = $this->config->item('role_home');

        return is_array($homes) && isset($homes[$role]) ? (string) $homes[$role] : 'employee/signin';
    }

    public function signin()
    {
        $data = [
            'title' => 'Employee Sign In',
            'enable_footer' => false,
        ];

        if ($this->input->post('signin_employee')) {
            $rules = $this->config->item('employee_signin');
            $this->form_validation->set_rules($rules);

            if ($this->form_validation->run() === true) {
                try {
                    $employee_id = (string) $this->input->post('employee_id', true);
                    $password = (string) $this->input->post('password', true);

                    $employee = $this->EmployeeModel->findByEmployeeId($employee_id);

                    if ((int) $employee['is_active'] !== 1) {
                        $this->toastify->error('This employee account has been deactivated.');
                    } elseif (password_verify($password, $employee['password'])) {
                        $this->toastify->success('Welcome back, ' . $employee['firstname'] . '.');
                        $this->session->set_userdata('employee_id', (int) $employee['employee_id']);
                        $this->session->set_userdata('username', $employee['username']);
                        $this->session->set_userdata('role', $employee['role']);
                        $this->session->set_userdata('email', $employee['email']);

                        redirect($this->roleHome(), 'refresh');
                    } else {
                        $this->toastify->error('Invalid employee ID or password.');
                    }
                }catch(AppException $e) {
                    $this->toastify->error($e->toToast());
                }
            }else {
                $this->toastify->error('Please correct the highlighted fields');
            }
        }

        $this->load->view('templates/header', $data);
        $this->load->view('auth/employee_signin', $data);
    }

    public function signout()
    {
        $this->session->unset_userdata(array('username', 'role', 'email', 'employee_id'));
        $this->toastify->success('You have been signed out.');
        redirect('employee/signin');
    }
}