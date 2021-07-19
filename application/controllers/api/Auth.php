<?php

use Restserver\Libraries\REST_Controller;
use Detection\MobileDetect;
defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Auth extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function login_post()
    {
        $username = $this->post('username');
        $password = $this->post('password');
		$remember_me = $this->post('remember_me');
		$app_version = new MobileDetect;
        $data = $this->post();

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('username', 'Username', 'required', [
            'required' => 'Username is required'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'required', [
            'required' => 'Password is required'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'code' => 400,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
            //ERROR AKAN DI GET OLEH REST CLIENT
        }

        $hash_password = md5($password);
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where('username', $username);
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {

            $user = $query->row();
            $authenticated = hash_equals($user->password, $hash_password);
            if ($authenticated) {
				$ip_address = $this->input->ip_address();

				$data_jwt_token = [
                    'user_id' => $user->id,
                    'username' => $user->username,
					'fullname' => $user->fullname,
					'photo_path'=> $user->photo_path,
					'email'=> $user->email,
                    'time' => time()+60*60,
					'remember_me' => $remember_me,
					'roles' => $user->roles
                ];

				if($remember_me || $app_version->isMobile()){

					//if remember me on (true) or a mobile login generate token jwt with refresh_token
					// expire time for refresh_token 1 month    *( 1 month ) : 60 * 60 * 24 * 30 = 2592000

					//check if remember_token exist to update the row base on ip (avoid data hoarding)
					$this->db->select('id,refresh_token');
					$this->db->where('username',$user->username);
					$this->db->where('ip_address',$ip_address);
					$query_remember = $this->db->get('remember_token');

					if ( $query_remember->num_rows() > 0 )
					{
						//if exist generate jwt token with old refresh_token
						$query =$query_remember->row();
						$data_jwt_token['refresh_token'] = $query->refresh_token;
						$jwt_token = $this->authorization_token->generateToken($data_jwt_token);

						//then update a new jwt token to database
						$data_remember = [
							'jwt_token' => $jwt_token,
						];
						$this->db->where('id',$query->id);
						$this->db->update('remember_token',$data_remember);
					} else {
						// if not exist generate jwt token with new refresh_token
						$refresh_token = password_hash($user->username,PASSWORD_BCRYPT);
						$data_jwt_token['refresh_token'] = $refresh_token;
						$jwt_token = $this->authorization_token->generateToken($data_jwt_token);

						//then insert a data remember_token to database
						$data_remember['username'] =$user->username;
						$data_remember['refresh_token'] =$refresh_token;
						$data_remember['ip_address'] = $ip_address;
						$data_remember['jwt_token'] = $jwt_token;
						$this->db->insert('remember_token', $data_remember);
						$insert_id = $this->db->insert_id();

						//Creating the scheduler for delete the remember_me data if the device is not mobile
						if (!$app_version->isMobile()){
							// expire remember_token is 1month
							$this->db->query("SET GLOBAL event_scheduler = ON");
							$this->db->query("CREATE EVENT event_refresh_token_$user->username$insert_id 
										ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 15 MINUTE
										DO DELETE FROM `remember_token` 
										WHERE `id`='$insert_id'");
						}
					}

				}else{
					// create jwt token without refresh_token inside
					$jwt_token = $this->authorization_token->generateToken($data_jwt_token);
				}


			} else {
                $message = [
                    'code' => 401,
                    'message' => 'Password Salah'
                ];
                $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
            }
        } else {
            $message = [
                'code' => 401,
                'message' => 'Username Salah'
            ];
            $this->response($message, REST_Controller::HTTP_UNAUTHORIZED);
        }

		$return_data = [
			'user_id' =>$user->id,
			'username'=> $user->username,
			'email'=> $user->email,
			'fullname'=> $user->fullname,
			'photo_path'=> $user->photo_path,
			'roles' => $user->roles,
			'token' => $jwt_token,

		];

        // Login Success
        $message = [
            'code' => 200,
            'data' => $return_data,
            'message' => "User login successful"
        ];
        $this->response($message, REST_Controller::HTTP_OK);


    }

	public function validate_token_post()
	{
		$auth = $this->authorization_token->validateTokenPost();
		if (($auth['status']) == false) {
			$this->response([
				'code' => 401,
				'message' => $auth['message'],
			], REST_Controller::HTTP_UNAUTHORIZED);

		}else{
			$this->response([
				'code' => 200,
				'message' => 'Access Token is valid.'.(time() - ($auth['data']->time)),
			], REST_Controller::HTTP_OK);
		}

	}

	public function refresh_token_post()
	{
		$jwt_token = $this->input->post('token', TRUE);
		$jwt_decode = $this->authorization_token->userDataPost();

		if (($jwt_decode['status']) == TRUE) {

			//check if jwt decode has data of refresh_token
			if ($jwt_decode['data']->remember_me){
				$refresh_token = $jwt_decode['data']->refresh_token;
				$this->db->select('id');
				$this->db->where('jwt_token',$jwt_token);
				$this->db->where('refresh_token',$refresh_token);
				$query_remember = $this->db->get('remember_token');

				if ( $query_remember->num_rows() > 0 )
				{

					//if exist refreshing jwt token with new expired time
					$jwt_decode['data']->time = time()+60*60;
					$jwt_token = $this->authorization_token->generateToken($jwt_decode['data']);

					//then update a refreshing jwt token to database
					$data_remember = [
						'jwt_token' => $jwt_token,
					];
					$this->db->where('id',$query_remember->row()->id);
					$this->db->update('remember_token',$data_remember);


					$return_data = [
						'user_id' => $jwt_decode['data']->user_id,
						'username'=> $jwt_decode['data']->username,
						'email'=> $jwt_decode['data']->email,
						'fullname'=> $jwt_decode['data']->fullname,
						'photo_path'=> $jwt_decode['data']->photo_path,
						'roles' => $jwt_decode['data']->roles,
						'token' => $jwt_token,
					];

					$message = [
						'code' => 200,
						'data' => $return_data,
						'message' => "Refresh token successful"
					];
					$this->response($message, REST_Controller::HTTP_OK);

				}

			}

			// if refreshing failed
			$this->response([
				'code' => 401,
				'message' => 'Refresh token is expired or not define, try login again.',
			], REST_Controller::HTTP_UNAUTHORIZED);

		}else{
			// if refreshing failed
			$this->response([
				'code' => 401,
				'message' => $jwt_decode['message'],
			], REST_Controller::HTTP_UNAUTHORIZED);
		}

	}



}
