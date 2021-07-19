<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Datatables extends REST_Controller
{
	function __construct()
	{
		parent::__construct();

		$auth = $this->authorization_token->validateToken();
		$this->load->model([
			'Size_chart_model',
		]);
		if (isset($auth['message'])) {
			if ($auth['message'])
				if ($auth['message'] == "Token Time Expire.") {
					$data = $this->authorization_token->userData();
					$verif = $this->authorization_token->generateToken($data);
				}else{
					$this->response([
						'code' => 401,
						'message' => 'Failed to procces, unauthorized,',
					], REST_Controller::HTTP_UNAUTHORIZED);
				}
		}
	}

	public function list_chart_size_post()
	{
		var_dump($this->input->post('start'));
		die;
		$result = json_decode($this->Cloth_model->get_list_chart_size());

		$resultArray = [];
		$start = $this->input->post('start');
		$rowIndex = $start + 1;

		if (isset($result->data)){
			foreach ($result->data as $k => $v) {
				$resultArray[] = [
					$rowIndex,
					htmlspecialchars($v->size_type, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->cloth_type, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->gender_type, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->chest_width, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->shirt_length, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->waist_width, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->sleeve_length, ENT_QUOTES, 'UTF-8'),
					htmlspecialchars($v->id, ENT_QUOTES, 'UTF-8'),
				];

				$rowIndex++;
			}
		}


		$result->data = $resultArray;

		$message = [
			'code' => 200,
			'data' => $result,
			'message' => "Success retrieve list chart size_post"
		];
		$this->response($message, REST_Controller::HTTP_OK);

	}



}
