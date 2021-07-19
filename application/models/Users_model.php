<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

	public function get_all()
	{
		return $this->db
			->select('id,email,username,fullname,roles')
			->order_by('users.id', 'DESC' )
			->from('users')
			->get()->result();
	}
	public function get_by_id($id)
	{

		return $this->db
			->select('id,email,username,fullname,roles,handphone,gender,address,photo_path')
			->from('users')
			->where('id',$id)
			->get()->row();
	}

	public function get_by_email($email)
	{
		return $this->db
			->select('email')
			->from('users')
			->where('email',$email)
			->get()->row();
	}
	public function get_by_username($username)
	{
		return $this->db
			->select('username')
			->from('users')
			->where('username',$username)
			->get()->row();
	}

	public function delete($id)
	{
		$this->db->delete('users', ['id' => $id]);
		return $this->db->affected_rows();
	}

	public function put($id,$data)
	{
		$this->db->update('users', $data, ['id' => $id]);
		return $this->db->affected_rows();
	}

	public function create($data)
	{
		$this->db->insert('users', $data);
		return $this->db->affected_rows();
	}

}

