<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_login;

class Login extends Controller
{

    protected $M_login;
    protected $session;

    public function __construct()
    {
        $this->M_login = new M_login(); // Memanggil model
        $this->session = session();
    }
    public function index()
    {
        return view('login');
    }

    public function token_generate()
    {
        return $tokens = md5(uniqid(rand(), true));
    }

    public function register()
    {
        return view('Register');
    }

    public function proses_login()
    {

        $request = service('request'); 
        $Username =  $request->getPost('Username');
        $Password = $request->getPost('Password');

            $cek =  $this->M_login->cekUser($Username);
            if ($cek->getNumRows() != 1) {
                $this->session->setflashdata('pesangagal', 'UserID Tidak Terdaftar');
                return redirect()->to(base_url('login'));
            } else {

                $isi = $cek->getRow();
                if (password_verify($Password, $isi->Password)) {
                    $data_session = array(
                        'Username' => $isi->Username,
                        'Active' => $isi->Active,
                        'ClientID' => $isi->ClientID,
                        'ClientSecret' => $isi->ClientSecret,
                        'Avatar' => $isi->Avatar
                    );

                    $this->session->set($data_session);

                    if ($isi->Active == 1) {
                        return redirect()->to(base_url('home'));
                    } else {
                        return redirect()->to(base_url('login'));
                        $this->session->setflashdata('pesangagal1', 'UserID Tidak Aktif');
                    }
                } else {
                    $this->session->setflashdata('pesangagal2', '<div class="alert alert-danger" role="alert">
					Pastikan Username & Password Benar!
				  </div>');
                    return redirect()->to(base_url('login'));
                }
            }
        } 

    public function proses_register()
    {
        $request = service('request'); // Ambil request
        $Username = $request->getPost('Username');
        $Password = $request->getPost('Password2');

        $Data = [
            'Username' => $Username,
            'Password' => $this->hash_password($Password),
            'Active' => 1
        ];

        $Data2 = [
            'Username' => $Username
        ];

        $cek = $this->M_login->cekUser2($Username);

        if ($cek->getNumRows() == 0) { // Perbaikan num_rows()
            $this->M_login->insertData('Ms_User', $Data);
            $this->M_login->insertData2('Ms_UserDetail', $Data2);
            $this->session->setFlashdata('pesan_berhasil', 'Berhasil Menambahkan akun (Username: ' . $Username . ')');
        } else {
            $this->session->setFlashdata('pesan_gagal', 'akun (Username: ' . $Username . ') Sudah Terdaftar');
        }

        return redirect()->to(base_url('users'));
    }

    private function hash_password($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    public function logout()
    {
        $this->load->model('m_login');
        $this->m_login->logout();
        redirect(base_url('Login'));
    }
}
