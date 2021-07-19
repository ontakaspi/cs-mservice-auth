<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Users extends REST_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->helper('url');
		$this->load->helper('string');
		$this->load->model([
			'Users_model',
		]);

		$this->auth = $this->authorization_token->validateToken();
		if (($this->auth['status']) ==false) {
			$this->response([
				'code' => 401,
				'message' => $this->auth['message'],
			], REST_Controller::HTTP_UNAUTHORIZED);
		}


	}


	public function index_get($id = null)
	{
		if (($this->auth['data']->roles) !== 'admin') {
			$this->response([
				'code' => 401,
				'message' => 'user doesnt have this authorization',
			], REST_Controller::HTTP_UNAUTHORIZED);
		}
		if ($id){
			$result = $this->Users_model->get_by_id($id);
		}else{
			$result = $this->Users_model->get_all();
		}


		if ($result){
			$message = [
				'code' => 200,
				'data' => $result,
				'message' => "Success retrieve users"
			];
			$this->response($message, REST_Controller::HTTP_OK);
		}else{
			if ($id){
				$message = [
					'code' => 404,
					'message' => 'users not found'
				];
			}else{
				$message = [
					'code' => 404,
					'message' => 'users has no record'
				];
			}
			$this->response($message, REST_Controller::HTTP_NOT_FOUND);
		}


	}


	public function delete_delete($id = null)
	{
		if (($this->auth['data']->roles) !== 'admin') {
			$this->response([
				'code' => 401,
				'message' => 'user doesnt have this authorization',
			], REST_Controller::HTTP_UNAUTHORIZED);
		}
		if (($this->auth['data']->roles) !== 'admin') {
			$this->response([
				'code' => 401,
				'message' => 'user doesnt have this authorization',
			], REST_Controller::HTTP_UNAUTHORIZED);
		}
		if (!$id) {
			$this->response([
				'code' => 400,
				'message' => 'Id is required, please send an id for users.'
			], REST_Controller::HTTP_BAD_REQUEST);
		}


		if ($this->Users_model->delete($id) > 0) {
			//Berhasil
			$this->response([
				'code' => 200,
				'id' => $id,
				'message' => 'users deleted.'
			], REST_Controller::HTTP_OK);
		} else {
			//id not found
			$this->response([
				'code' => 404,
				'message' => 'Data null or id not found.'
			], REST_Controller::HTTP_NOT_FOUND);
		}

	}

	public function create_post()
	{
		if (($this->auth['data']->roles) !== 'admin') {
			$this->response([
				'code' => 401,
				'message' => 'user doesnt have this authorization',
			], REST_Controller::HTTP_UNAUTHORIZED);
		}
		$data = $this->post();
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('username', 'Username', 'required|is_unique[users.username]', [
			'required' => 'username is required'
		]);
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]', [
			'required' => 'Email is required'
		]);
		$this->form_validation->set_rules('fullname', 'Name', 'required', [
			'required' => 'fullname is required'
		]);
		$this->form_validation->set_rules('roles', 'roles', 'required', [
			'required' => 'roles is required'
		]);
		$this->form_validation->set_rules('password', 'password', 'required', [
			'required' => 'password is required'
		]);
		$this->form_validation->set_message('is_unique', 'The %s is already taken');

		if ($this->form_validation->run() == FALSE) {
			$this->response([
				'code' => 400,
				'message' => validation_errors()
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		$message = [
			'username' => $this->post('username'),
			'fullname' => $this->post('fullname'),
			'email' => $this->post('email'),
			'roles' => $this->post('roles'),
			'password' => md5($this->post('roles')),
			'created_on' => date('Y-m-d'),
		];

		if ($this->Users_model->create($message) > 0) {
			$this->response([
				'code' => 200,
				'data' => $data,
				'message' => 'users has been created.'
			], REST_Controller::HTTP_OK);
		} else {
			$this->response([
				'code' => 400,
				'message' => 'No data created'
			], REST_Controller::HTTP_BAD_REQUEST);
		}

	}

	public function update_put($id =null)
	{
		if (($this->auth['data']->roles) !== 'admin') {
			$this->response([
				'code' => 401,
				'message' => 'user doesnt have this authorization',
			], REST_Controller::HTTP_UNAUTHORIZED);
		}
		if (!$id) {
			$this->response([
				'code' => 400,
				'message' => 'Id is required, please send an id for chart size.'
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		$data = $this->put();
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('username', 'Username', 'required', [
			'required' => 'username is required'
		]);
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email', [
			'required' => 'Email is required'
		]);
		$this->form_validation->set_rules('fullname', 'Name', 'required', [
			'required' => 'fullname is required'
		]);
		$this->form_validation->set_rules('roles', 'roles', 'required', [
			'required' => 'roles is required'
		]);
		$this->form_validation->set_rules('password', 'password', 'required', [
			'required' => 'password is required'
		]);

		if ($this->form_validation->run() == FALSE) {
			$this->response([
				'code' => 400,
				'message' => validation_errors()
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		$message = [
			'username' => $this->put('username'),
			'fullname' => $this->put('fullname'),
			'email' => $this->put('email'),
			'roles' => $this->put('roles'),
			'created_on' => date('Y-m-d'),
		];

		if ( $this->put('password')){
			$message['password'] = md5($this->put('password'));
		}

		$check_email = $this->Users_model->get_by_email($this->put('email'));
			if ($check_email){
				if ($this->put('email') !== $check_email->email){
					$this->response([
						'code' => 400,
						'message' => 'Email already taken'
					], REST_Controller::HTTP_BAD_REQUEST);
				}
			}

		$check_username = $this->Users_model->get_by_username($this->put('username'));
		if ($check_username){
			if ($this->put('username') !== $check_username->username){
				$this->response([
					'code' => 400,
					'message' => 'Username already taken'
				], REST_Controller::HTTP_BAD_REQUEST);
			}
		}

		if ($this->Users_model->put($id,$message) > 0) {
			//Berhasil
			$this->response([
				'code' => 200,
				'id' => $id,
				'message' => 'users updated.'
			], REST_Controller::HTTP_OK);
		} else {
			//id not found
			$this->response([
				'code' => 404,
				'message' => 'No data change or id not found.'
			], REST_Controller::HTTP_NOT_FOUND);
		}

	}

	public function profile_put($show=null, $id =null)
	{
		if (!$id) {
			$this->response([
				'code' => 400,
				'message' => 'Id is required, please send an id for users profile.'
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		$data = $this->put();
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('fullname', 'Fullname', 'required', [
			'required' => 'Fullname is required'
		]);
		$this->form_validation->set_rules('handphone', 'Handphone', 'numeric', [
			'required' => 'Email is required'
		]);

		if ($this->form_validation->run() == FALSE) {
			$this->response([
				'code' => 400,
				'message' => validation_errors()
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		switch ($show) {
			case 'update':
				$message = [
					'fullname' => $this->put('fullname'),
					'handphone' => $this->put('handphone'),
				];

				$gender_array = array("Pria","Wanita");
				if ( $this->put('gender')){

					if ( in_array( $this->put('gender') , $gender_array ) == FALSE ) {
						$this->response([
							'code' => 400,
							'message' => 'Gender only accept value : Pria/Wanita.'
						], REST_Controller::HTTP_BAD_REQUEST);

					}else{
						$message['gender'] = $this->put('gender');
					}

				}
				if ( $this->put('address')){
					$message['address'] = $this->put('address');
				}

				if ($this->Users_model->put($id,$message) > 0) {
					//Berhasil
					$this->response([
						'code' => 200,
						'id' => $id,
						'message' => 'users profile updated.'
					], REST_Controller::HTTP_OK);
				} else {
					//id not found
					$this->response([
						'code' => 404,
						'message' => 'No data change or id not found.'
					], REST_Controller::HTTP_NOT_FOUND);
				}
				break;
			default:

				break;
		}




	}

	public function file_check()
	{
		if (isset($_FILES['image']['size']) && $_FILES['image']['size'] != 0) {

			//file uploud
			$ekstensi =  array('jpeg', 'png', 'jpg');

			$tipe_file = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			if (!in_array(strtolower($tipe_file), $ekstensi)) {
				$this->form_validation->set_message('file_check', 'File upload the correct extension (\'jpeg\', \'png\', \'jpg\')');
				return false;
			}
			if ($_FILES['image']['size'] > 15000000) {
				$this->form_validation->set_message('file_check', 'Max upload images size is 15mb');
				return false;
			}
			return true;
		} else {
			$this->form_validation->set_message('file_check', 'image files is required');
			return false;
		}
	}

	public function avatar_post($method,$id)
	{
		if (!$id) {
			$this->response([
				'code' => 400,
				'message' => 'Id is required, please send an id for users profile.'
			], REST_Controller::HTTP_BAD_REQUEST);
		}

		switch ($method) {
			case 'upload':
				//prevent error that size uploud server can do show
				if (ob_get_length() > 0 ) {
					ob_get_contents();
					ob_end_clean();
				}
				//validation size of server uploud max size not more than 100mb
				if((int)$_SERVER['CONTENT_LENGTH'] > 100000000){
					$this->response([
						'code' => 400,
						'message' => 'Max upload images size is 15mb'
					], REST_Controller::HTTP_BAD_REQUEST);

				}
				$data =  $_FILES;
				$this->form_validation->set_data($data);
				$this->form_validation->set_rules('image', 'image', 'callback_file_check');

				if ($this->form_validation->run() == FALSE) {
					$this->response([
						'code' => 400,
						'message' => validation_errors()
					], REST_Controller::HTTP_BAD_REQUEST);
				}

				$data_users= $this->db
					->select('photo_path,username')
					->from('users')
					->where('id',$id)
					->get()->row();

				if (isset($data_users->photo_path)) {
					$get_path = str_replace(base_url(),'',$data_users->photo_path);
					$path = getcwd() .$get_path;
					if (file_exists($data_users->photo_path)) {
						unlink($path);
					}
				}


				$type_file = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
				$filename = 'AVA-' . $data_users->username.random_string('alnum', 5).'.' . $type_file;
				$tmp = $_FILES['image']['tmp_name'];
				move_uploaded_file($tmp, getcwd() . '/assets/avatar/' . $filename);
				$url_path = base_url().'assets/avatar/'.$filename;

				$data = array(
					'photo_path'                => $url_path,
				);

				$this->db->update('users', $data, ['id' => $id]);
				if ($this->db->affected_rows() > 0) {
					//Berhasil
					$this->response([
						'code' => 200,
						'photo_path' => $url_path,
						'message' => 'Users avatar changed.'
					], REST_Controller::HTTP_OK);
				} else {
					//id not found
					$this->response([
						'code' => 404,
						'message' => 'No data change or id not found.'
					], REST_Controller::HTTP_NOT_FOUND);
				}
				break;
			case 'remove':

				$data_users= $this->db
					->select('photo_path,username')
					->from('users')
					->where('id',$id)
					->get()->row();

				if (isset($data_users->photo_path)) {
					$get_path = str_replace(base_url(),'',$data_users->photo_path);
					$path = getcwd() .$get_path;
					if (file_exists($data_users->photo_path)) {
						unlink($path);
					}
				}else{
					$this->response([
						'code' => 200,
						'id' => $id,
						'message' => 'No data changed'
					], REST_Controller::HTTP_OK);
				}

				$data = array(
					'photo_path'                => null,
				);

				$this->db->update('users', $data, ['id' => $id]);
				if ($this->db->affected_rows() > 0) {
					//Berhasil
					$this->response([
						'code' => 200,
						'photo_path' => null,
						'message' => 'users avatar removed.'
					], REST_Controller::HTTP_OK);
				} else {
					//id not found
					$this->response([
						'code' => 404,
						'message' => 'Data null or id not found.'
					], REST_Controller::HTTP_NOT_FOUND);
				}
				break;
			default:
				$this->response([
					'code' => 400,
					'message' => 'please write uploud / remove metode in link segment 4'
				], REST_Controller::HTTP_BAD_REQUEST);
				break;
		}


	}


	public function delete_ava_post()
	{


		$data_mitra = $this->db->get_where('mitra', ['id_qonstanta' => $this->session->userdata('id_qonstanta')])->row();

		$path = getcwd() . '/vendors/indentitas_mitra/avatar/' . $data_mitra->ava;
		if (file_exists($path)) {
			unlink($path);
		}

		$data = array(
			'ava'                => null,
		);
		$this->mitra->update(['id_qonstanta' => $this->session->userdata('id_qonstanta')], $data, 'mitra');


		echo json_encode($data);
	}


}
