<?php

namespace App\Models;

use CodeIgniter\Model;

class M_login extends Model
{

    public function insertData($table, $Data)
    {
        return $this->db->table($table)->insert($Data);
    }

    public function insertData2($table, $Data2)
    {
        return $this->db->table($table)->insert($Data2);
    }

    public function cekUsername($Username)
    {
        return $this->select('Username')
                    ->where('Username', $Username)
                    ->first(); // Menggunakan first() agar hanya mengembalikan satu hasil
    }

    public function cekUser($Username)
    {
        return $this->db->table('Ms_User a')
                        ->select('a.Password, a.Username, a.Active, b.*')
                        ->join('Ms_UserDetail b', 'b.Username = a.Username', 'left')
                        ->where('a.Username', $Username)
                        ->get();
                        
    }


    public function editUser($where, $data)
    {
        return $this->db->table('users')
                        ->where($where)
                        ->update($data);
    }

    public function cekUser2($Username)
    {
        return $this->db->table('Ms_User')->where('Username', $Username)->get();
    }
}
?>
