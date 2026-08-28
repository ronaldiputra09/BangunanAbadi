<?php

namespace App\Models;

use CodeIgniter\Model;

class M_Admin extends Model
{
    public function Data_Foto($Username)
    {
        return  $this->db
            ->table('Ms_UserDetail')
            ->select('*', FALSE)
            ->where('Username', $Username)->get()->getResult();
    }

    public function deleteData($table, $where)
    {
        return $this->db->table($table)->where($where)->delete();
    }

    public function simpan_settings($table, $Where, $Data)
    {
        return $this->db->table($table)->where($Where)->update($Data);
    }

    public function simpan_avatar($table, $Where, $Data)
    {
        return $this->db->table($table)->where($Where)->update($Data);
    }

    public function update_password($table, $Where, $Data)
    {
        return $this->db->table($table)->where($Where)->update($Data);
    }

    public function getData($table, $where = [])
    {
        return $this->db->table($table)
            ->where($where)
            ->get()
            ->getResultArray();
    }

    // Tambahan di M_Admin
    public function getData2($table, $transactionNos = [])
    {
        return $this->db->table($table)
            ->whereIn('transactionNo', $transactionNos)
            ->get()
            ->getResultArray();
    }

    public function getDataSalesReceipt2($table, $transactionNos = [])
    {
        return $this->db->table($table)
            ->select("
            {$table}.transactionNo,
            {$table}.customerNo,
            {$table}.customerName,
            {$table}.total,
            {$table}.transactionDate,
            Payment.BankCode
        ")
            ->distinct()
            ->join('Payment', "Payment.TransactionNo = {$table}.transactionNo", 'left')
            ->whereIn("{$table}.transactionNo", $transactionNos)
            ->get()
            ->getResultArray();
    }

    public function getDataSalesReceipt($transactionNos = [])
    {
        if (empty($transactionNos)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($transactionNos), '?'));

        $sql = "
       SELECT 
    a.*,
    CAST(TRUNCATE(a.Jumlah,6) AS CHAR) AS Jumlah1,
    b.cabang,
    CONCAT(
        a.TransactionNo,
        '-',
        LPAD(
            ROW_NUMBER() OVER (
                PARTITION BY a.TransactionNo 
                ORDER BY a.id
            ),
            2,
            '0'
        )
    ) AS TransactionNo_Urut
FROM Payment a
LEFT JOIN (
    SELECT DISTINCT TransactionNo, cabang
    FROM invoice_penjualan
) b ON b.TransactionNo = a.TransactionNo 
WHERE a.TransactionNo IN ($placeholders)
    ";
        $query = $this->db->query($sql, $transactionNos);
        return $query->getResultArray();
    }

    public function DataClient($Username)
    {
        return $this->db->table('Ms_UserDetail')
            ->select('*')
            ->where('Username', $Username)
            ->get()
            ->getResult();
    }

    public function ClientID($Username)
    {
        $query = $this->db->table('Ms_UserDetail')
            ->select('ClientID')
            ->where('Username', $Username)
            ->get()
            ->getRow(); // Ambil satu baris hasil query

        return $query ? $query->ClientID : null; // Kembalikan nilai ClientID atau null jika tidak ditemukan
    }

    public function insertData($table, $Data)
    {
        return $this->db->table($table)->insert($Data);
    }

    public function DataLog($Username)
    {
        return $this->db->table('log')
            ->select('*')
            ->where('Username', $Username)
            ->get()
            ->getResult();
    }

    public function DataUsers($Username)
    {
        return $this->db->table('Ms_User')
            ->select('*')
            ->whereNotIn('Username', $Username)
            ->orderBy('CreatedTime', 'DESC')
            ->get()
            ->getResult();
    }

    public function getUserById($id)
    {
        return $this->db->table('Ms_UserDetail')
            ->where('Username', $id)
            ->get()
            ->getRow();
    }

    public function hapusUser($Username)
    {

        $this->db->table('Ms_UserDetail')->delete(['Username' => $Username]);
        return $this->db->table('Ms_User')->delete(['Username' => $Username]);
    }

    public function hapusLog($Username)
    {
        return $this->db->table('log')->delete(['Username' => $Username]);
    }

    public function getDataByDateRange($table, $dateColumn, $startDate, $endDate, $where = [], $select = '*')
    {
        return $this->db->table($table)
            ->select($select)
            ->where($where)
            ->where("$dateColumn BETWEEN '$startDate' AND '$endDate'")
            ->get()
            ->getResultArray();
    }

    public function getDataPenerimaanBarangWithPembelian($start, $end)
    {
        return $this->db->table('penerimaan_barang a')
            ->select('a.*, b.transactionNo as NomorPO')
            ->join('pembelian b', 'b.IdNo = a.detailidpo', 'left')
            ->where('a.transactionDate >=', $start)
            ->where('a.transactionDate <=', $end)
            ->distinct()
            ->get()
            ->getResultArray();
    }

    public function getDataPenerimaan($transactionNo)
    {
        return $this->db->table('penerimaan_barang a')
            ->select('a.*, b.transactionNo as nomorPO')
            ->join('pembelian b', 'b.IdNo = a.detailidpo', 'left')
            ->where('a.transactionNo', $transactionNo)
            ->distinct()
            ->get()
            ->getResultArray();
    }

    public function getDataInvoicePembelian($start, $end)
    {
        return $this->db->table('invoice_pembelian a')
            ->select('a.*, b.transactionNo as nomorRI')
            ->join('penerimaan_barang b', 'b.IdNo = a.detailidgrn', 'left')
            ->where('a.transactionDate >=', $start)
            ->where('a.transactionDate <=', $end)
            ->distinct()
            ->get()
            ->getResultArray();
    }

    public function getDataInvoicePembelian2($transactionNo)
    {
        return $this->db->table('invoice_pembelian a')
            ->select('a.*, b.transactionNo as nomorRI')
            ->join('penerimaan_barang b', 'b.IdNo = a.detailidgrn', 'left')
            ->where('a.transactionNo', $transactionNo)
            ->distinct()
            ->get()
            ->getResultArray();
    }



    public function getDataByDateRange2($table, $dateColumn, $startDate, $endDate, $where = [], $columns = ['transactionNo', 'total'])
    {
        return $this->db->table($table)
            ->select(implode(',', $columns))
            ->distinct()
            ->where($where)
            ->where("$dateColumn BETWEEN '$startDate' AND '$endDate'")
            ->orderBy('transactionNo', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getSingleTransactionSummary($transactionNo)
    {
        $query = $this->db->query("SELECT 
    a.*,
    CAST(TRUNCATE(a.Jumlah,6) AS CHAR) AS Jumlah1,
    b.cabang,
    CONCAT(
        a.TransactionNo,
        '-',
        LPAD(
            ROW_NUMBER() OVER (
                PARTITION BY a.TransactionNo 
                ORDER BY a.id
            ),
            2,
            '0'
        )
    ) AS TransactionNo_Urut
FROM Payment a
LEFT JOIN (
    SELECT DISTINCT TransactionNo, cabang
    FROM invoice_penjualan
) b ON b.TransactionNo = a.TransactionNo 
WHERE a.TransactionNo = '$transactionNo';");
        return $query->getResultArray();
    }
}


