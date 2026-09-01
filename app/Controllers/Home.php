<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_Admin;
use CodeIgniter\Files\File;
use App\Libraries\ApiBangunanService;


class Home extends BaseController
{

  protected $M_Admin;
  protected $session;

  public function __construct()
  {
    $this->M_Admin = new M_Admin(); // Memanggil model
    $this->session = session();
  }
  public function index()
  {
    if ($this->session->get('Active') == 1) {
      return view('Home');
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function profile()
  {
    if ($this->session->get('Active') == 1) {
      $Username = $this->session->get('Username');
      $Data['Avatar'] = $this->M_Admin->Data_Foto($Username);
      return view('Profile', $Data);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function proses_new_password()
  {

    $request = service('request');
    $Username = $request->getPost('Username');
    $Password = $request->getPost('Password2');

    $Data = array(
      'Username' => $Username,
      'Password' => $this->hash_password($Password)
    );
    $Where = array(
      'Username' => $Username
    );

    $this->M_Admin->update_password('Ms_User', $Where, $Data);
    $this->session->setflashdata('msg_berhasil', 'berhasil');
    return redirect()->to(base_url('home/profile'));
  }

  public function Avatar()
  {
    $file = $this->request->getFile('Avatar');
    $Username = $this->request->getPost('Username');

    if ($file->isValid() && !$file->hasMoved()) {
      $newName = $file->getRandomName();
      $file->move(ROOTPATH . 'upload', $newName); // Simpan ke folder 'public/upload'

      // Data untuk update ke database
      $Data = [
        'Avatar' => $newName
      ];

      $Where = array(
        'Username' => $Username
      );

      // Update ke database
      $this->M_Admin->simpan_avatar('Ms_UserDetail', $Where, $Data);

      // Set flashdata & redirect
      session()->setFlashdata('msg_berhasil1', 'berhasil');
      return redirect()->to(base_url('home/profile'));
    } else {
      session()->setFlashdata('msg_gagal', 'Upload gagal: ' . $file->getErrorString());
      return redirect()->to(base_url('home/profile'));
    }
  }


  public function settings()
  {
    if ($this->session->get('Active') == 1) {
      $Username = $this->session->get('Username');
      $Data['Avatar'] = $this->M_Admin->Data_Foto($Username);
      $Data['DataClient'] = $this->M_Admin->DataClient($Username);
      return view('Setting', $Data);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function simpan_settings()
  {
    $request = service('request'); // Ambil request
    $Username = $request->getPost('Username');
    $ClientID = $request->getPost('ClientID');
    $ClientSecret = $request->getPost('ClientSecret');

    $Data = array(
      'Username' => $Username,
      'ClientID' => $ClientID,
      'ClientSecret' => $ClientSecret
    );
    $Where = array(
      'Username' => $Username
    );

    $this->M_Admin->simpan_settings('Ms_UserDetail', $Where, $Data);

    $this->session->setflashdata('pesan_berhasil', 'berhasil');
    return redirect()->to(base_url('home/settings'));
  }

  public function Logout()
  {
    session_destroy();
    return view('login');
  }

  private function hash_password($Password)
  {
    return password_hash($Password, PASSWORD_DEFAULT);
  }


  public function migrasi()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      $api = new ApiBangunanService();

      // Ambil data kontak pelanggan
      $responsePelanggan = $api->getData('contact_search', [
        'Sort'   => 'id DESC',
        'Filter' => "kategori LIKE '%Pelanggan%'",
        'Limit'  => 1000
      ]);

      $responsePemasok = $api->getData('contact_search', [
        'Sort'   => 'id DESC',
        'Filter' => "kategori LIKE '%Pemasok%'",
        'Limit'  => 1000
      ]);


      // Ambil data barang
      $responseItems = $api->getData('item_search', [
        'Sort'   => 'id DESC',
        'Limit'  => 1000
      ]);

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('MasterData', [
        'accessToken' => session()->get('access_token'),
        'dbSession' => session()->get('accurate_session'),
        'accurateHost' => session()->get('accurate_host'),
        'pelanggan'    => $responsePelanggan['Data'] ?? [],
        'pemasok'    => $responsePemasok['Data'] ?? [],
        'karyawan'    => $responseKaryawan['Data'] ?? [],
        'items'       => $responseItems['Data'] ?? []
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function sync_master_item()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      $api = new ApiBangunanService();

      // Ambil data kontak pelanggan
      $responsePelanggan = $api->getData('contact_search', [
        'Sort'   => 'id DESC',
        'Filter' => "kategori LIKE '%Pelanggan%'",
        'Limit'  => 200
      ]);

      $responsePemasok = $api->getData('contact_search', [
        'Sort'   => 'id DESC',
        'Filter' => "kategori LIKE '%Pemasok%'",
        'Limit'  => 200
      ]);


      // Ambil data barang
      $responseItems = $api->getData('item_search', [
        'Sort'   => 'id DESC',
        'Limit'  => 200
      ]);

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('SyncMasterItem', [
        'accessToken' => $accessToken,
        'dbSession'   => $dbSession,
        'accurateHost' => $accurateHost,
        'pelanggan'    => $responsePelanggan['Data'] ?? [],
        'pemasok'    => $responsePemasok['Data'] ?? [],
        'karyawan'    => $responseKaryawan['Data'] ?? [],
        'items'       => $responseItems['Data'] ?? []
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function sync_transaction()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('SyncTrans', [
        'accessToken' => $accessToken,
        'dbSession'   => $dbSession,
        'accurateHost' => $accurateHost
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function get_data_manually()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('GetDataManual', [
        'accessToken' => $accessToken,
        'dbSession'   => $dbSession,
        'accurateHost' => $accurateHost
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function sync_transactionNo()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('SyncTransNo', [
        'accessToken' => $accessToken,
        'dbSession'   => $dbSession,
        'accurateHost' => $accurateHost
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }



  public function log()
  {
    if ($this->session->get('Active') == 1) {

      $Username = session()->get('Username');
      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      $Data['DataLog'] = $this->M_Admin->DataLog($Username);

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('log', $Data, [
        'accessToken' => session()->get('access_token'),
        'dbSession' => session()->get('accurate_session'),
        'accurateHost' => session()->get('accurate_host')
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function users()
  {
    if ($this->session->get('Active') == 1) {

      $Username = $this->session->get('Username');
      $Data['DataUsers'] = $this->M_Admin->DataUsers([$Username]);

      return view('Users', $Data);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }


  public function deleteUser($Username)
  {
    if ($this->session->get('Active') == 1) {
      $this->M_Admin->hapusUser($Username);
      $this->session->setFlashdata('pesan_berhasil', 'Berhasil menghapus akun (Username: ' . $Username . ')');
      return redirect()->to(base_url('users'));
    } else {
      $this->session->setFlashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function deleteLog()
  {
    if ($this->session->get('Active') == 1) {
      $Username = $this->session->get('Username');
      $this->M_Admin->hapusLog($Username);
      $this->session->setFlashdata('pesan_berhasil', 'Berhasil menghapus Log');
      return redirect()->to(base_url('home/log'));
    } else {
      $this->session->setFlashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }

  public function get_data_manuall_no()
  {
    if ($this->session->get('Active') == 1) {

      $accessToken = session()->get('access_token');
      $dbSession = session()->get('accurate_session');
      $accurateHost = session()->get('accurate_host');

      if (!$accessToken || !$dbSession) {
        $this->session->setflashdata('pilihdb', 'pilih database');
        return redirect()->to(base_url('auth/db-list'));
      }

      return view('GetDataByNo', [
        'accessToken' => $accessToken,
        'dbSession'   => $dbSession,
        'accurateHost' => $accurateHost
      ]);
    } else {
      $this->session->setflashdata('Harus_Login', 'berhasil');
      return redirect()->to(base_url('login'));
    }
  }
}
