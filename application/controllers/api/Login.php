<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Login extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index_post()
    {
        $username = $this->post('username');
        $password = $this->post('password');

        $data = $this->post();

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('username', 'Username', 'required', [
            'required' => 'Username wajib di isi'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'required', [
            'required' => 'Password wajib di isi'
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
                $data = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'time' => time(),
                    'expired_time' => time() + 86400,
                ];

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

        $user_token = $this->authorization_token->generateToken($data);
        // var_dump($data);
        // die;

        $return_data = [
            'user_id' => $user->id,
            'username' => $user->username,
			'email' => $user->email,
			'fullname' => $user->fullname,
            'token' => $user_token,
        ];

        // Login Success
        $message = [
            'code' => 200,
            'data' => $return_data,
            'message' => "User login successful"
        ];
        $this->response($message, REST_Controller::HTTP_OK);


    }

    public function user_post()
    {
        print_r($this->authorization_token->userData());
    }

    //function bawaan dari library rest server
    // public function index_get()
    // {
    //     //cek di GET ada parameter id atau nggak
    //     $id = $this->get('id');
    //     //kalau ga ada id
    //     if ($id === null) {
    //         $harga = $this->mhs->getHarga();

    //     } else {
    //         //kalau ada id nya, dia ngambil id
    //         $harga = $this->mhs->getHarga($id);

    //     }
    //     //alias mhs nya
    //      //kalau harga ada isinya, tampilin data harga itu
    //      if ($harga) {
    //         $this->response([
    //             'status' => true,
    //             'data' => $harga
    //         ], REST_Controller::HTTP_OK);
    //     } else {
    //         //kalau id nya tidak ketemu
    //         $this->response([
    //             'status' => 400,
    //             'message' => 'id not found'
    //         ], REST_Controller::HTTP_NOT_FOUND);
    //     }
    // }
    //function bawaan dari library rest server
    // public function index_delete() {
    //     $id = $this->delete('id');

    //     //tanpa id
    //     if ($id === null) {
    //         $this->response([
    //             'status' => false,
    //             'message' => 'provide an id'
    //         ], REST_Controller::HTTP_BAD_REQUEST);
    //     } else {
    //         if ($this->mhs->deleteMahasiswa($id) > 0) {
    //             //Berhasil
    //             $this->response([
    //                 'status' => true,
    //                 'id' => $id,
    //                 'message' => 'deleted.'
    //             ], REST_Controller::HTTP_NO_CONTENT);
    //         } else {
    //             //id not found
    //             $this->response([
    //                 'status' => false,
    //                 'message' => 'id not found'
    //             ], REST_Controller::HTTP_BAD_REQUEST);
    //         }
    //     }
    // }

    // public function index_post()
    // {
    //     $data = [
    //         'username' => $this->post('username'),
    //         'password' => $this->post('password')
    //     ];


    //     $cek = $this->db->select("*")
    //         ->where($data)
    //         ->get("user")->num_rows();

    //     //sukses
    //     if ($cek > 0) {
    //         $roles = $this->db->select("*")
    //             ->join("roles", "roles.roles_id = user.roles_id")
    //             ->where($data)
    //             ->get("user")->row();
    //         $query = [
    //             'user_id' => $roles->user_id,
    //             'username' => $roles->username,
    //             'roles_id' => $roles->roles_id,
    //             'roles_name' => $roles->roles_name,
    //             'user_entity' => $roles->user_entity,
    //             'bank_id' => $roles->bank_id,
    //             'tag' => $roles->tag,
    //             'bank_name' => '',
    // 			'email' => $roles->email,
    // 			'nama' => $roles->nama,
    // 			'description' => $roles->description,
    // 			'photo' => $roles->photo,
    //         ];
    //         if ($roles->tag == 'broker') {
    //             $this->db->select('name');
    //             $this->db->from('admin.mst_broker');
    //             $this->db->where('id', $roles->bank_id);
    //             $broker = $this->db->get()->row();
    //             if ($broker) {
    //                 $query['bank_name'] = $broker->name;
    //             }
    //         } elseif ($roles->tag == 'insurance') {
    //             $this->db->select('name');
    //             $this->db->from('admin.mst_asuransi');
    //             $this->db->where('id', $roles->bank_id);
    //             $insurance = $this->db->get()->row();
    //             if ($insurance) {
    //                 $query['bank_name'] = $insurance->name;
    //             }
    //         } else {
    //             $this->db->select('bank_name AS name, parent_id');
    //             $this->db->from('admin.mst_bank');
    //             $this->db->where('id', $roles->bank_id);
    //             $this->db->where('rowstate', 'active');
    //             $bank = $this->db->get()->row();
    //             if ($bank) {
    //                 $query['bank_name'] = $bank->name;
    //                 // SET BRANCH
    //                 if (isset($bank->parent_id)) {
    //                     $query['bank_id'] = $bank->parent_id;
    //                     $query['branch_id'] = $roles->bank_id;
    //                 }
    //             }
    //         }

    //         $this->response([
    //             'status' => true,
    //             'message' => 'Kamu berhasil login',
    //             'data' => $query
    //         ], REST_Controller::HTTP_CREATED);
    //     } else if (!$data['username'] && !$data['password']) {
    //         //gagal
    //         $this->response([
    //             'status' => false,
    //             'message' => 'Harap isi username dan password'
    //         ], REST_Controller::HTTP_CREATED);
    //     } else if (!$data['password']) {
    //         //gagal
    //         $this->response([
    //             'status' => false,
    //             'message' => 'Password harus di isi'
    //         ], REST_Controller::HTTP_CREATED);
    //     } else if (!$data['username']) {
    //         //gagal
    //         $this->response([
    //             'status' => false,
    //             'message' => 'Username harus di isi'
    //         ], REST_Controller::HTTP_CREATED);
    //     } else {
    //         //gagal
    //         $this->response([
    //             'status' => false,
    //             'message' => 'Username atau Password salah'
    //         ], REST_Controller::HTTP_CREATED);
    //     }
    // }



    // public function index_put()
    // {
    //     //nanti id nya masuk ke WHERE nya
    //     $id = $this->put('id');
    //     $data = [
    //         'nrp' => $this->put('nrp'),
    //         'nama' => $this->put('nama'),
    //         'email' => $this->put('email'),
    //         'jurusan' => $this->put('jurusan')
    //     ];

    //      //sukses
    //      if ($this->mhs->updateMahasiswa($data, $id) > 0) {
    //         $this->response([
    //             'status' => true,
    //             'message' => 'new mahasiswa has been updated'
    //         ], REST_Controller::HTTP_NO_CONTENT);
    //     } else {
    //         //gagal
    //         $this->response([
    //             'status' => false,
    //             'message' => 'failed to update data'
    //         ], REST_Controller::HTTP_BAD_REQUEST);
    //     }
    // }
}
