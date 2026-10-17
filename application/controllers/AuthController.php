<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthController extends CI_Controller {

    //Entry point for authentication.
    public function index()
    {
        if ($this->session->userdata('employee_id')) {
            $homes = $this->config->item('role_home');
            $role = $this->session->userdata('employee_role');
            redirect(is_array($homes) && isset($homes[$role]) ? $homes[$role] : 'employee/dashboard');
        }

        if ($this->session->userdata('username')) {
            redirect('home');
        }

        $this->signin();
    }

    //Signs the customer out and clears their session.
    public function signout()
    {
        $this->session->unset_userdata(array('username', 'role', 'email'));
        $this->toastify->success('You have been signed out.');
        redirect('auth/signin');
    }

    /*
     * Shows the sign-in form and authenticates submitted credentials, sending
     * the customer to the home page.
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
                        redirect('home');
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

        //Prefills come from the checkout "create an account" flow so the
        //delivery email and address survive into the new account.
        $prefillEmail = (string) $this->input->get('email', true);
        $prefillAddress = (string) $this->input->get('address', true);

        if ($this->input->post('register')) {

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

                    //Carry the checkout delivery address into the account.
                    $address = (string) $this->input->post('address', true);
                    if ($address !== '') {
                        $user['address'] = $address;
                    }

                    $response = $this->UserModel->registerUser($user);
                    if($response) {
                        deliver_email(
                            'emails/welcome_email',
                            [
                                'subject'  => 'Welcome to Circuit Cart',
                                'greeting' => "Dear " . ucfirst((string) $this->input->post('firstname', TRUE)) . ",",
                                'message'  => 'Welcome to Circuit Cart',
                            ],
                            (string) $this->input->post('email', TRUE),
                            'Welcome to Circuit Cart'
                        );
                        $this->toastify->success("Registration successul");

                        //Coming from checkout: continue straight through.
                        redirect($this->input->post('return_to') ? 'checkout' : 'auth/signin');
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

        $data['prefill_email'] = $prefillEmail;
        $data['prefill_address'] = $prefillAddress;

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

	//Renders the forgot-password form. Public route (no login required).
	public function forgot()
	{
		if ($this->session->userdata('username')) {
			redirect('settings', 'refresh');
			return;
		}

		$data = [
			'title' => 'Forgot Password',
			'enable_footer' => false,
		];

		$this->load->view('templates/header', $data);
		$this->load->view('auth/forgot_password', $data);
	}

	//Issues a single-use reset token by email. Never reveals whether an
	//account exists; always shows the same generic success message.
	public function requestReset()
	{
		$email = strtolower(trim((string) $this->input->post('email', true)));

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->toastify->error('Enter a valid email address.');
			redirect('auth/forgot', 'refresh');
			return;
		}

		try {
			$user = $this->UserModel->findUserByEmail($email);

			if (!empty($user['id'])) {
				$token = bin2hex(random_bytes(32));
				$hash = hash('sha256', $token);
				$expires = date('Y-m-d H:i:s', time() + 3600);

				//Invalidate any prior unused tokens for this user
				$this->db->where('user_id', $user['id']);
				$this->db->where('used_at IS NULL', NULL, FALSE);
				$this->db->delete('tbl_password_resets');

				$this->db->insert('tbl_password_resets', [
					'user_id' => (int) $user['id'],
					'token_hash' => $hash,
					'expires_at' => $expires,
				]);

				$reset_url = site_url('auth/reset/' . $token);
				deliver_email('emails/reset_email', [
					'subject' => 'Reset your Circuit Cart password',
					'greeting' => 'Hello,',
					'message' => 'We received a request to reset the password for this account.',
					'reset_url' => $reset_url,
					'expires_minutes' => 60,
				], $email, 'Reset your Circuit Cart password');
			}
		} catch (Exception $e) {
			//Swallow errors so the endpoint never reveals account existence
		}

		$this->toastify->success('If an account matches that email, a reset link has been sent.');
		redirect('auth/signin', 'refresh');
	}

	//Renders the new-password form for a valid, unexpired token.
	public function reset($token = '')
	{
		if ($this->session->userdata('username')) {
			redirect('settings', 'refresh');
			return;
		}

		$valid = $this->validateResetToken($token) !== NULL;

		$data = [
			'title' => 'Reset Password',
			'enable_footer' => false,
			'token' => $token,
			'token_valid' => $valid,
		];

		$this->load->view('templates/header', $data);
		$this->load->view('auth/reset_password', $data);
	}

	//Consumes the token and stores the new password.
	public function applyReset()
	{
		$token = (string) $this->input->post('token', true);
		$password = (string) $this->input->post('password', true);
		$confirm = (string) $this->input->post('confirm_password', true);

		if (strlen($password) < 6) {
			$this->toastify->error('Password must be at least 6 characters.');
			redirect('auth/reset/' . $token, 'refresh');
			return;
		}

		if ($password !== $confirm) {
			$this->toastify->error('Passwords do not match.');
			redirect('auth/reset/' . $token, 'refresh');
			return;
		}

		$row = $this->validateResetToken($token);

		if ($row === NULL) {
			$this->toastify->error('This reset link is invalid or has expired. Please request a new one.');
			redirect('auth/forgot', 'refresh');
			return;
		}

		try {
			$this->db->where('id', (int) $row['user_id']);
			$this->db->update('tbl_users', [
				'password' => password_hash($password, PASSWORD_DEFAULT),
			]);

			$this->db->where('id', (int) $row['id']);
			$this->db->update('tbl_password_resets', [
				'used_at' => date('Y-m-d H:i:s'),
			]);

			$this->toastify->success('Password updated. You can sign in now.');
			redirect('auth/signin', 'refresh');
		} catch (Exception $e) {
			$this->toastify->error('We are currently experiencing technical difficulties.');
			redirect('auth/forgot', 'refresh');
		}
	}

	//Looks up a token: returns the matching reset row when valid and
	//unexpired, or NULL. When $mark is true the row is also marked used.
	private function validateResetToken(string $token): ?array
	{
		if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
			return NULL;
		}

		$hash = hash('sha256', $token);
		$this->db->where('token_hash', $hash);
		$this->db->where('used_at IS NULL', NULL, FALSE);
		$query = $this->db->get('tbl_password_resets', 1);
		$row = $query ? $query->row_array() : NULL;

		if (!$row) {
			return NULL;
		}

		if (strtotime($row['expires_at']) < time()) {
			return NULL;
		}

		return $row;
	}
}
