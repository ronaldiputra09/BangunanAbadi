<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DateTime;
use CodeIgniter\Controller;
use App\Models\M_Admin;



class Auth extends Controller
{
    protected $M_Admin;
    protected $session;

    public function __construct()
    {
        $this->M_Admin = new M_Admin();
        $this->session = session();
    }

    public function index()
    {
        $Username = $this->session->get('Username');
        $ClientID = $this->M_Admin->ClientID($Username);


        if (!$ClientID) {
            $this->session->setflashdata('clientgagal', 'gagal konek');
            return redirect()->to(base_url(relativePath: 'home'));
        }

        $authUrl = "https://account.accurate.id/oauth/authorize"
            . "?response_type=token" // Gunakan token
            . "&client_id=" . $ClientID
            . "&redirect_uri=" . urlencode(getenv('ACCURATE_REDIRECT_URI'))
            . "&scope=" . urlencode("receive_item_delete receive_item_view purchase_invoice_delete sales_invoice_delete purchase_return_delete purchase_return_save purchase_return_view receive_item_view employee_save employee_view stock_opname_result_delete stock_opname_result_view stock_opname_result_save stock_opname_order_delete stock_opname_order_view stock_opname_order_save roll_over_delete roll_over_view roll_over_save material_adjustment_delete material_adjustment_view material_adjustment_save job_order_delete job_order_view job_order_save item_adjustment_delete item_adjustment_view item_adjustment_save item_transfer_delete item_transfer_view item_transfer_save bank_transfer_delete bank_transfer_view bank_transfer_save other_deposit_delete other_deposit_view other_deposit_save other_payment_delete other_payment_view other_payment_save journal_voucher_delete journal_voucher_view purchase_order_delete journal_voucher_save purchase_payment_save vendor_save vendor_view receive_item_save purchase_order_save sales_order_save warehouse_view item_category_view item_category_save shipment_view shipment_save customer_save item_view item_save unit_view unit_save delivery_order_save delivery_order_delete delivery_order_view customer_view branch_view branch_save sales_receipt_view sales_receipt_save purchase_invoice_save purchase_invoice_view sales_invoice_save sales_invoice_view purchase_order_view sales_return_save sales_return_view sales_return_delete");

        return redirect()->to($authUrl);
    }

    public function callback()
    {
        return view('Auth/Callback');
    }

    public function store_token()
    {
        $accessToken = $this->request->getJSON()->access_token;

        if (!$accessToken) {
            return $this->response->setJSON(["error" => "Access token missing."])->setStatusCode(400);
        }

        session()->set('access_token', $accessToken);


        return $this->response->setJSON(["message" => "Access token stored successfully!", "access_token" => $accessToken]);
    }



    public function dbList()
    {
        $accessToken = session()->get('access_token');

        if (!$accessToken) {
            return redirect()->to(base_url('auth'));
        }

        $url = "https://account.accurate.id/api/db-list.do";

        // Request menggunakan cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = $this->decodeAccurateResponse($response, $httpCode, $curlError);

        if (!$this->isAccurateSuccess($data, $httpCode) || !isset($data['d']) || !is_array($data['d'])) {
            $message = $this->accurateErrorMessage($data, $curlError);
            log_message('error', "Accurate db-list gagal (HTTP {$httpCode}): {$message}");

            return $this->response->setJSON(['error' => $message])->setStatusCode(400);
        }

        return view('db_list', ['databases' => $data['d'], 'error' => null]);
    }

    public function openDatabase()
    {
        $accessToken = session()->get('access_token');
        $dbId = $this->request->getPost('db_id');

        if (!$accessToken || !$dbId) {
            $this->session->setflashdata('pilihdb', 'berhasil konek');
            return redirect()->to(base_url('auth/db-list'));
        }

        $url = "https://account.accurate.id/api/open-db.do?id=" . $dbId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $data = $this->decodeAccurateResponse($response, $httpCode, $curlError);

        if (!$this->isAccurateSuccess($data, $httpCode) || empty($data['session']) || empty($data['host'])) {
            $message = $this->accurateErrorMessage($data, $curlError);
            log_message('error', "Accurate open-db gagal (HTTP {$httpCode}): {$message}");
            $this->session->setflashdata('gagal', $message);
            return redirect()->to(base_url('auth/db-list'));
        }

        session()->set('accurate_session', $data['session']);
        session()->set('selected_db', $dbId);
        session()->set('accurate_host', $data['host']);
        $this->session->setflashdata('berhasil', 'berhasil konek');
        return redirect()->to(base_url('auth/db-list'));
    }

    public function insert_masterPelanggan()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_pelanggan');
        $nama = $this->request->getPost('nama_pelanggan');
        $kategori = $this->request->getPost('kategori_pelanggan');
        $kontak = $this->request->getPost('kontakperson_pelanggan');
        $alamat = $this->request->getPost('alamat_pelanggan');
        $notelp = $this->request->getPost('notelp_pelanggan');
        $termin = $this->request->getPost('termin_pelanggan');
        $tanggal = $this->request->getPost('tanggal');

        // Loop data
        foreach ($kode as $i => $customerNo) {
            if (empty($customerNo)) continue;

            $transactionNo = trim($customerNo);

            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'customerNo' => trim($transactionNo),
                'name' => trim($nama[$i] ?? ''),
                'categoryName' => 'Umum',
                'contact' => trim($kontak[$i] ?? ''),
                'billStreet' => trim($alamat[$i] ?? ''),
                'workPhone' => trim($notelp[$i] ?? ''),
                'termName' => trim($termin[$i] ?? ''),
                'transDate' => isset($tanggal[$i]) ? trim($tanggal[$i]) : date('d/m/Y')
            ];

            // Kirim data baru (tanpa id = selalu dianggap insert)
            $url = $accurateHost . "/accurate/api/customer/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }



        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "SyncCustomer"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }

    public function insert_masterItem()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_item');
        $barcode = $this->request->getPost('barcode');
        $nama = $this->request->getPost('nama_item');
        $satuan = $this->request->getPost('satuan_item');
        $hargabeli = $this->request->getPost('hargabeli');
        $hargajual = $this->request->getPost('hargajual1');

        // Loop data
        foreach ($kode as $i => $no) {
            if (empty($no)) continue;

            $transactionNo = trim($no);

            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'no' => trim($no),
                'name' => trim($nama[$i]),
                'upcNo' => trim($barcode[$i] ?? ''),
                'unit1Name' => trim($satuan[$i] ?? ''),
                'vendorPrice' => trim($hargabeli[$i] ?? ''),
                'unitPrice' => trim($hargajual[$i] ?? ''),
                'itemType' => 'INVENTORY',


            ];

            // Kirim data baru (tanpa id = selalu dianggap insert)
            $url = $accurateHost . "/accurate/api/item/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }



        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Insert_Item"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }


    public function sync_masterPelanggan()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_pelanggan');
        $nama = $this->request->getPost('nama_pelanggan');
        $kategori = $this->request->getPost('kategori_pelanggan');
        $kontak = $this->request->getPost('kontakperson_pelanggan');
        $alamat = $this->request->getPost('alamat_pelanggan');
        $notelp = $this->request->getPost('notelp_pelanggan');
        $termin = $this->request->getPost('termin_pelanggan');
        $tanggal = $this->request->getPost('tanggal');

        // Loop data
        foreach ($kode as $i => $customerNo) {
            if (empty($customerNo)) continue;

            $transactionNo = trim($customerNo);
            $idCustomer = null;
            $page = 1;

            do {
                $checkUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,customerNo&sp.page={$page}&sp.pageSize=100";


                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);

                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check customer {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['customerNo']) && trim($item['customerNo']) === $transactionNo) {
                            $idCustomer = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'customerNo' => trim($customerNo),
                'name' => trim($nama[$i] ?? ''),
                'categoryName' => 'Umum',
                'contact' => trim($kontak[$i] ?? ''),
                'billStreet' => trim($alamat[$i] ?? ''),
                'workPhone' => trim($notelp[$i] ?? ''),
                'termName' => trim($termin[$i] ?? ''),
                'transDate' => isset($tanggal[$i]) ? trim($tanggal[$i]) : date('d/m/Y')
            ];

            if ($idCustomer) {
                $postData['id'] = $idCustomer; // maka save.do akan update
            }

            $url = $accurateHost . "/accurate/api/customer/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }


        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "SyncCustomer"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }

    public function sync_masterPemasok()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_pemasok');
        $nama = $this->request->getPost('nama_pemasok');
        $kategori = $this->request->getPost('kategori_pemasok');
        $kontak = $this->request->getPost('kontakperson_pemasok');
        $alamat = $this->request->getPost('alamat_pemasok');
        $notelp = $this->request->getPost('notelp_pemasok');
        $termin = $this->request->getPost('termin_pemasok');
        $tanggal = $this->request->getPost('tanggalpemasok');

        // Loop data
        foreach ($kode as $i => $vendorNo) {
            if (empty($vendorNo)) continue;

            $transactionNo = trim($vendorNo);

            $idVendor = null;
            $page = 1;

            do {
                $checkUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,vendorNo&sp.page={$page}&sp.pageSize=100";


                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);

                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check vendor {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['vendorNo']) && trim($item['vendorNo']) === $transactionNo) {
                            $idVendor = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);
            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'vendorNo' => trim($vendorNo),
                'name' => trim($nama[$i]),
                'categoryName' => 'Umum',
                'contact' => trim($kontak[$i] ?? ''),
                'billStreet' => trim($alamat[$i] ?? ''),
                'workPhone' => trim($notelp[$i] ?? ''),
                // 'termName' => trim($termin[$i] ?? ''),
                'transDate' => isset($tanggal[$i]) ? trim($tanggal[$i]) : date('d/m/Y')
            ];

            if ($idVendor) {
                $postData['id'] = $idVendor; // maka save.do akan update
            }

            $url = $accurateHost . "/accurate/api/vendor/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "SyncSupplier"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }

    public function sync_masterKaryawan()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_karyawan');
        $nama = $this->request->getPost('nama_karyawan');
        $kategori = $this->request->getPost('kategori_karyawan');
        $kontak = $this->request->getPost('kontakperson_karyawan');
        $alamat = $this->request->getPost('alamat_karyawan');
        $notelp = $this->request->getPost('notelp_karyawan');
        $termin = $this->request->getPost('termin_karyawan');
        $tanggal = $this->request->getPost('tanggalkaryawan');

        // Loop data
        foreach ($kode as $i => $number) {
            if (empty($number)) continue;

            $transactionNo = trim($number);

            // Cek vendor existing
            $checkUrl = $accurateHost . "/accurate/api/employee/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number";
            $ch = curl_init($checkUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID
            ]);
            $checkResponse = curl_exec($ch);
            curl_close($ch);

            $checkData = json_decode($checkResponse, true);
            log_message('debug', "Check employee {$transactionNo}: " . json_encode($checkData));
            $idVendor = null;
            if (isset($checkData['s']) && $checkData['s'] && isset($checkData['d']) && is_array($checkData['d'])) {
                foreach ($this->accurateRecords($checkData) as $item) {
                    if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                        $idVendor = $item['id'];
                        break;
                    }
                }
            }
            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'number' => trim($number),
                'name' => trim($nama[$i]),
                'categoryName' => 'Umum',
                'contact' => trim($kontak[$i] ?? ''),
                'billStreet' => trim($alamat[$i] ?? ''),
                'workPhone' => trim($notelp[$i] ?? ''),
                'termName' => trim($termin[$i] ?? ''),
                'transDate' => isset($tanggal[$i]) ? trim($tanggal[$i]) : date('d/m/Y')
            ];

            if ($idVendor) {
                $postData['id'] = $idVendor; // maka save.do akan update
            }

            $url = $accurateHost . "/accurate/api/employee/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "SyncEmployee"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }

    public function sync_masterItem()
    {
        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        // Ambil data dari form
        $kode = $this->request->getPost('kode_item');
        $barcode = $this->request->getPost('barcode');
        $nama = $this->request->getPost('nama_item');
        $satuan = $this->request->getPost('satuan_item');
        $hargabeli = $this->request->getPost('hargabeli');
        $hargajual = $this->request->getPost('hargajual1');


        // Loop data
        foreach ($kode as $i => $no) {
            if (empty($no)) continue;

            $transactionNo = trim($no);

            $idItem = null;
            $page = 1;

            do {
                $checkUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,no&sp.page={$page}&sp.pageSize=100";


                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);

                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check items {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['no']) && trim($item['no']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);
            // Siapkan data baru untuk dikirim ke Accurate
            $postData = [
                'no' => trim($no),
                'name' => trim($nama[$i]),
                'upcNo' => trim($barcode[$i] ?? ''),
                'unit1Name' => trim($satuan[$i] ?? ''),
                'vendorPrice' => trim($hargabeli[$i] ?? ''),
                'unitPrice' => trim($hargajual[$i] ?? ''),
                'itemType' => 'INVENTORY',


            ];

            if ($idItem) {
                $postData['id'] = $idItem; // maka save.do akan update
            }

            $url = $accurateHost . "/accurate/api/item/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $accessToken,
                "X-Session-ID: " . $sessionID,
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "SyncItem"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncMasterItem'));
    }


    public function sync_PurchaseOrder()
    {
        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken  = session()->get('access_token');
        $sessionID    = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username    = session()->get('Username');
        $logImport   = [];
        $successCount = 0;
        $failCount    = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data  = $model->getDataByDateRange('pembelian', 'transactionDate', $start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            $grouped[$noTransaksi][] = $row;
        }

        $checkedVendors = [];
        $checkedItems   = [];

        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page   = 1;

            // CEK DAN SIMPAN SUPPLIER
            $vendorNo = $header['vendorNo'];
            if (!isset($checkedVendors[$vendorNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['vendorNo']) === $vendorNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['vendorName'] ?? $vendorNo,
                        'vendorNo' => $vendorNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Vendor [$vendorNo]: " . $vendorResp);
                }

                $checkedVendors[$vendorNo] = true;
            }

            // CEK APAKAH PO SUDAH ADA DI ACCURATE
            do {
                $checkUrl = $accurateHost . "/accurate/api/purchase-order/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check PO {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s']) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }
                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break;
                }
            } while ($page <= $totalPages);

            // BANGUN PAYLOAD
            $postData = [
                'number' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'vendorNo' => $vendorNo,
                'description' => $header['description'] ?? '',
                'taxable' => ($header['tax'] == 'PPN') ? true : false,
                'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
                'toAddress' => 'TANGERANG',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'warehouseName' => 'Gudang Pusat',
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/purchase-order/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);
                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete PO [$transactionNo]: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus PO lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }

            $url = $accurateHost . "/accurate/api/purchase-order/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', "POST Purchase Order [$transactionNo]: " . json_encode($postData));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', "Response [$transactionNo]: " . $response);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Order"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }

    public function sync_ReceiveItem()
    {

        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataPenerimaanBarangWithPembelian($start, $end);

        // Grouping
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $checkedVendors = [];
        $checkedItems   = [];

        // 4. Proses tiap transaksi
        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page = 1;

            // CEK DAN SIMPAN SUPPLIER
            $vendorNo = $header['vendorNo'];
            if (!isset($checkedVendors[$vendorNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['vendorNo']) === $vendorNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['vendorName'] ?? $vendorNo,
                        'vendorNo' => $vendorNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Vendor [$vendorNo]: " . $vendorResp);
                }

                $checkedVendors[$vendorNo] = true;
            }

            do {
                // Cek apakah transaksi sudah ada di Accurate
                $checkUrl = $accurateHost . "/accurate/api/receive-item/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check receive item {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Buat payload untuk dikirim
            $postData = [
                'number' => $transactionNo,
                'receiveNumber' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'vendorNo' => $header['vendorNo'],
                'description' => $header['description'] ?? '',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'warehouseName' => 'Gudang Pusat',
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            // --- 3. Kalau ketemu ID, lanjut hapus
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/receive-item/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);

                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete Response for {$transactionNo}: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus data lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }


            $url = $accurateHost . "/accurate/api/receive-item/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Receive_Item"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }

    public function sync_PurchaseInvoice()
    {

        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('invoice_pembelian', 'transactionDate', $start, $end);

        // Grouping
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $checkedVendors = [];
        $checkedItems   = [];

        // 4. Proses tiap transaksi
        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page = 1;

            // CEK DAN SIMPAN SUPPLIER
            $vendorNo = $header['vendorNo'];
            if (!isset($checkedVendors[$vendorNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['vendorNo']) === $vendorNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['vendorName'] ?? $vendorNo,
                        'vendorNo' => $vendorNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Vendor [$vendorNo]: " . $vendorResp);
                }

                $checkedVendors[$vendorNo] = true;
            }

            do {
                // Cek apakah transaksi sudah ada di Accurate
                $checkUrl = $accurateHost . "/accurate/api/purchase-invoice/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check receive item {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Buat payload untuk dikirim
            $postData = [
                'number' => $transactionNo,
                'billNumber' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'vendorNo' => $header['vendorNo'],
                'description' => $header['description'] ?? '',
                'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            // --- 3. Kalau ketemu ID, lanjut hapus
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/purchase-invoice/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);

                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete Response for {$transactionNo}: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus data lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }


            $url = $accurateHost . "/accurate/api/purchase-invoice/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Invoice"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }

    public function sync_PurchaseReturn()
    {

        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('retur_pembelian', 'transactionDate', $start, $end);

        // Grouping
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $checkedVendors = [];
        $checkedItems   = [];

        // 4. Proses tiap transaksi
        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page = 1;

            // CEK DAN SIMPAN SUPPLIER
            $vendorNo = $header['vendorNo'];
            if (!isset($checkedVendors[$vendorNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['vendorNo']) === $vendorNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['vendorName'] ?? $vendorNo,
                        'vendorNo' => $vendorNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Vendor [$vendorNo]: " . $vendorResp);
                }

                $checkedVendors[$vendorNo] = true;
            }

            do {
                // Cek apakah transaksi sudah ada di Accurate
                $checkUrl = $accurateHost . "/accurate/api/purchase-return/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check receive item {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Buat payload untuk dikirim
            $postData = [
                'number' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'vendorNo' => $header['vendorNo'],
                'taxable' => ($header['tax'] == 'PPN') ? true : false,
                'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
                'description' => $header['description'] ?? '',
                'returnType' => 'NO_INVOICE',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'warehouseName' => 'Gudang Pusat',
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            // --- 3. Kalau ketemu ID, lanjut hapus
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/purchase-return/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);

                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete Response for {$transactionNo}: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus data lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }


            $url = $accurateHost . "/accurate/api/purchase-return/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Return"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }

    public function sync_SalesInvoice()
    {

        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('invoice_penjualan', 'transactionDate', $start, $end);

        // Grouping
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $checkedCustomer = [];
        $checkedItems   = [];

        // 4. Proses tiap transaksi
        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page = 1;

            // CEK DAN SIMPAN CUSTOMER
            $customerNo = $header['customerNo'];
            if (!isset($checkedCustomer[$customerNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($customerNo) . "&fields=id,customerNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['customerNo']) === $customerNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['customerName'] ?? $customerNo,
                        'customerNo' => $customerNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/customer/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Customer [$customerNo]: " . $vendorResp);
                }

                $checkedCustomer[$customerNo] = true;
            }

            do {
                // Cek apakah transaksi sudah ada di Accurate
                $checkUrl = $accurateHost . "/accurate/api/sales-invoice/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check receive item {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Buat payload untuk dikirim
            $postData = [
                'number' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'customerNo' => $header['customerNo'],
                'taxable' => ($header['tax'] == 'PPN') ? true : false,
                'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
                'description' => $header['description'] ?? '',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'warehouseName' => 'Gudang Pusat',
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            // --- 3. Kalau ketemu ID, lanjut hapus
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/sales-invoice/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);

                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete Response for {$transactionNo}: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus data lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }


            $url = $accurateHost . "/accurate/api/sales-invoice/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Invoice"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }


    public function sync_SalesReturn()
    {

        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return redirect()->back()->with('error', 'Akses tidak diizinkan.');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID = session()->get('accurate_session');

        if (empty($accurateHost) || empty($accessToken) || empty($sessionID)) {
            return redirect()->back()->with('error', 'Session atau Access Token tidak ditemukan.');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('retur_penjualan', 'transactionDate', $start, $end);

        // Grouping
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $checkedCustomer = [];
        $checkedItems   = [];

        // 4. Proses tiap transaksi
        foreach ($grouped as $transactionNo => $detailRows) {
            $header = $detailRows[0];
            $idItem = null;
            $page = 1;

            // CEK DAN SIMPAN CUSTOMER
            $customerNo = $header['customerNo'];
            if (!isset($checkedCustomer[$customerNo])) {
                $checkVendorUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($customerNo) . "&fields=id,customerNo";

                $ch = curl_init($checkVendorUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkVendorResponse = curl_exec($ch);
                curl_close($ch);
                $checkVendorData = json_decode($checkVendorResponse, true);

                $isVendorExist = false;
                if (isset($checkVendorData['s']) && $checkVendorData['s']) {
                    foreach ($checkVendorData['d'] as $vendor) {
                        if (trim($vendor['customerNo']) === $customerNo) {
                            $isVendorExist = true;
                            break;
                        }
                    }
                }

                if (!$isVendorExist) {
                    $postVendor = [
                        'name' => $header['customerName'] ?? $customerNo,
                        'customerNo' => $customerNo,
                        'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                    ];
                    $ch = curl_init($accurateHost . "/accurate/api/customer/save.do");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID",
                        "Content-Type: application/json"
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
                    $vendorResp = curl_exec($ch);
                    curl_close($ch);
                    log_message('debug', "Save Customer [$customerNo]: " . $vendorResp);
                }

                $checkedCustomer[$customerNo] = true;
            }

            do {
                // Cek apakah transaksi sudah ada di Accurate
                $checkUrl = $accurateHost . "/accurate/api/sales-return/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";

                $ch = curl_init($checkUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID"
                ]);
                $checkResponse = curl_exec($ch);
                curl_close($ch);
                $checkData = json_decode($checkResponse, true);
                log_message('debug', "Check receive item {$transactionNo} (page {$page}): " . json_encode($checkData));

                if (isset($checkData['s'], $checkData['d']) && $checkData['s'] && is_array($checkData['d'])) {
                    foreach ($this->accurateRecords($checkData) as $item) {
                        if (isset($item['number']) && trim($item['number']) === $transactionNo) {
                            $idItem = $item['id'];
                            break 2;
                        }
                    }

                    $totalPages = $checkData['sp']['pageCount'] ?? 1;
                    $page++;
                } else {
                    break; // error, token expired, atau lainnya
                }
            } while ($page <= $totalPages);


            // Buat payload untuk dikirim
            $postData = [
                'number' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                'customerNo' => $header['customerNo'],
                'description' => $header['description'] ?? '',
                'returnType' => 'NO_INVOICE',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($detailRows as $row) {
                $itemNo = $row['itemNo'];
                if (!isset($checkedItems[$itemNo])) {
                    $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";

                    $ch = curl_init($checkItemUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $this->configureAccurateCurl($ch);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        "X-Session-ID: $sessionID"
                    ]);
                    $checkItemResponse = curl_exec($ch);
                    curl_close($ch);
                    $checkItemData = json_decode($checkItemResponse, true);

                    $isItemExist = false;
                    if (isset($checkItemData['s']) && $checkItemData['s']) {
                        foreach ($checkItemData['d'] as $item) {
                            if (trim($item['no']) === $itemNo) {
                                $isItemExist = true;
                                break;
                            }
                        }
                    }

                    if (!$isItemExist) {
                        $postItem = [
                            'name' => $row['itemName'],
                            'no' => $itemNo,
                            'itemType' => 'INVENTORY',
                            'Unit1Name' => $row['itemUnitName']
                        ];
                        $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $this->configureAccurateCurl($ch);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer $accessToken",
                            "X-Session-ID: $sessionID",
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postItem));
                        $itemResp = curl_exec($ch);
                        curl_close($ch);
                        log_message('debug', "Save Item [$itemNo]: " . $itemResp);
                    }

                    $checkedItems[$itemNo] = true;
                }

                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            // --- 3. Kalau ketemu ID, lanjut hapus
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/sales-return/delete.do?id=" . $idItem;
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $accessToken,
                    "X-Session-ID: " . $sessionID
                ]);
                $deleteResponse = curl_exec($ch);
                curl_close($ch);

                $deleteResult = json_decode($deleteResponse, true);
                log_message('debug', "Delete Response for {$transactionNo}: " . $deleteResponse);

                if (!isset($deleteResult['s']) || $deleteResult['s'] === false) {
                    $failCount++;
                    $logImport[] = [
                        'transactionNo' => $transactionNo,
                        'status' => 'Gagal',
                        'message' => 'Gagal menghapus data lama: ' . implode(', ', $deleteResult['d'] ?? ['Unknown error'])
                    ];
                    continue;
                }
            }


            $url = $accurateHost . "/accurate/api/sales-return/save.do";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);
            log_message('debug', 'Response Accurate: ' . $response);
            log_message('debug', 'HTTP Code: ' . $httpCode);

            if ($httpCode !== 200 || (isset($responseData['s']) && $responseData['s'] === false)) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => implode(', ', $responseData['d'] ?? ['Terjadi kesalahan'])
                ];
            } else {
                $successCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Berhasil',
                    'message' => 'Data berhasil dikirim'
                ];
            }
        }

        // Simpan log ke sesi
        session()->setFlashdata('logImport', $logImport);
        session()->setFlashdata('successCount', $successCount);
        session()->setFlashdata('failCount', $failCount);
        session()->setFlashdata('showAlert', true);

        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Return"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return redirect()->to(base_url('SyncTransaction'));
    }

    public function auto_sync()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));

        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('grn_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $data = $response['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru = $grouped;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {

            $this->M_Admin->deleteData('penerimaan_barang', ['transactionNo' => $transactionNo]);
            foreach ($detailRows as $row) {
                $Data = [
                    'idNo'   => $row['utamaid'],
                    'transactionNo'   => $transactionNo,
                    'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                        ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                        : '0000-00-00',

                    'vendorNo'        => $row['utamasupplierkode'],
                    'vendorName'        => $row['utamasuppliernama'],
                    'description'     => $row['utamauraian'] ?? '',
                    'cabang'     => $row['utamanamacabang'] ?? '',
                    'itemNo'          => $row['detailkodebarang'],
                    'itemName'        => $row['detailnamabarang'],
                    'quantity'        => floatval($row['detailjmlbarang']),
                    'unitPrice'       => floatval($row['detailharga']),
                    'discount'        => floatval($row['detailjmldiskon']),
                    'itemUnitName'    => $row['detailsatuan'],
                    'warehouse'       => $row['utamagudang'],
                    'detailNotes'     => $row['detailcatatan'],
                    'detailidgrn'     => $row['detailidpo'],
                ];

                $this->M_Admin->insertData('penerimaan_barang', $Data);
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function auto_sync_pembelian()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));

        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('po_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $data = $response['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru =  $grouped;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {

            $this->M_Admin->deleteData('pembelian', ['transactionNo' => $transactionNo]);
            foreach ($detailRows as $row) {
                $Data = [
                    'idNo'   => $row['utamaid'],
                    'transactionNo'   => $transactionNo,
                    'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                        ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                        : '0000-00-00',

                    'vendorNo'        => $row['utamasupplierkode'],
                    'vendorName'        => $row['utamasuppliernama'],
                    'description'     => $row['utamauraian'] ?? '',
                    'termasukpajak'     => $row['utamahargatermasukpajak'] ?? '',
                    'cabang'     => $row['utamanamacabang'] ?? '',
                    'itemNo'          => $row['detailkodebarang'],
                    'itemName'        => $row['detailnamabarang'],
                    'quantity'        => floatval($row['detailjmlbarang']),
                    'unitPrice'       => floatval($row['detailharga']),
                    'discount'        => floatval($row['detailjmldiskon']),
                    'itemUnitName'    => $row['detailsatuan'],
                    'warehouse'       => $row['utamagudang'],
                    'tax'       => $row['detailpajak1'],
                    'detailNotes'     => $row['detailcatatan']
                ];

                $this->M_Admin->insertData('pembelian', $Data);
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function auto_sync_invoice_pembelian()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));

        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('ri_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $data = $response['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru = $grouped;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {

            $this->M_Admin->deleteData('invoice_pembelian', ['transactionNo' => $transactionNo]);
            foreach ($detailRows as $row) {
                $Data = [
                    'transactionNo'   => $transactionNo,
                    'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                        ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                        : '0000-00-00',

                    'vendorNo'        => $row['utamasupplierkode'],
                    'vendorName'        => $row['utamasuppliernama'],
                    'description'     => $row['utamauraian'] ?? '',
                    'uraian'     => $row['utamauraian'] ?? '',
                    'cabang'     => $row['utamanamacabang'] ?? '',
                    'termasukpajak'     => $row['utamahargatermasukpajak'] ?? '',
                    'itemNo'          => $row['detailkodebarang'],
                    'itemName'        => $row['detailnamabarang'],
                    'quantity'        => floatval($row['detailjmlbarang']),
                    'unitPrice'       => floatval($row['detailharga']),
                    'discount'        => floatval($row['detailjmldiskon']),
                    'itemUnitName'    => $row['detailsatuan'],
                    'tax'       => $row['detailpajak1'],
                    'detailNotes'     => $row['detailcatatan'],
                    'detailidgrn'     => $row['detailidgrn'],
                ];
                $this->M_Admin->insertData('invoice_pembelian', $Data);
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function auto_sync_retur_pembelian()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));
        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('prt_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $data = $response['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru = $grouped;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {

            $this->M_Admin->deleteData('retur_pembelian', ['transactionNo' => $transactionNo]);
            foreach ($detailRows as $row) {
                $Data = [
                    'transactionNo'   => $transactionNo,
                    'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                        ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                        : '0000-00-00',

                    'vendorNo'        => $row['utamasupplierkode'],
                    'vendorName'        => $row['utamasuppliernama'],
                    'description'     => $row['utamauraian'] ?? '',
                    'cabang'     => $row['utamanamacabang'] ?? '',
                    'itemNo'          => $row['detailkodebarang'],
                    'itemName'        => $row['detailnamabarang'],
                    'quantity'        => floatval($row['detailjmlbarang']),
                    'unitPrice'       => floatval($row['detailharga']),
                    'discount'        => floatval($row['detailjmldiskon']),
                    'itemUnitName'    => $row['detailsatuan'],
                    'warehouse'       => $row['utamagudang'],
                    'tax'       => $row['detailpajak1'],
                    'termasukpajak'       => $row['utamahargatermasukpajak'],
                    'detailNotes'     => $row['detailcatatan']
                ];

                $this->M_Admin->insertData('retur_pembelian', $Data);
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function auto_sync_invoice_penjualan()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));

        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('si_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $response2 = $api->getData('si_pay_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit'  => 1000
        ]);


        $data = $response['Data'] ?? [];
        $data2 = $response2['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        $grouped2 = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        foreach ($data2 as $row2) {
            $noTransaksi2 = $row2['utamanotransaksi'];
            if (!isset($grouped2[$noTransaksi2])) {
                $grouped2[$noTransaksi2] = [];
            }
            $grouped2[$noTransaksi2][] = $row2;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru = $grouped;
        $transaksiPayment = $grouped2;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {
            try {
                $this->M_Admin->deleteData('invoice_penjualan', ['transactionNo' => $transactionNo]);
                foreach ($detailRows as $row) {
                    $Data = [
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',

                        'customerNo'        => $row['utamacustomerkode'],
                        'customerName'        => $row['utamacustomernama'],
                        'salesman'        => $row['utamasalesmankode'],
                        'termasukpajak'          => $row['utamahargatermasukpajak'] ?? '',
                        'description'     => $row['utamauraian'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => floatval($row['detailjmlbarang']),
                        'unitPrice'       => floatval($row['detailharga']),
                        'discount'        => floatval($row['detailjmldiskon']),
                        'itemUnitName'    => $row['detailsatuan'],
                        'warehouse'       => $row['utamagudang'],
                        'tax'       => $row['detailpajak1'],
                        'detailNotes'     => $row['detailcatatan'],
                        'caraBayar'     => $row['utamacarabayarnama'],
                        'total' => floatval($row['utamatotaltransaksi']),
                    ];

                    $this->M_Admin->insertData('invoice_penjualan', $Data);
                }
            } catch (\Throwable $th) {
                log_message('error', 'Gagal insert Payment: ' . $th->getMessage());
            }
        }

        foreach ($transaksiPayment as $transactionNo => $detailRows) {
            try {
                $this->M_Admin->deleteData('Payment', ['TransactionNo' => $transactionNo]);
                foreach ($detailRows as $row) {
                    $Data2 = [
                        'id'   => $row['utamaid'],
                        'TransactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',

                        'CustomerID'        => $row['utamakodecustomer'],
                        'CustomerName'        => $row['utamanamacustomer'],
                        'TransactionNo'        => $transactionNo,
                        'BankCode'     => $row['bayarrekbankkode'] ?? '',
                        'Jumlah'        => $row['bayarjumlah'] ?? '',
                        'caraBayar'        => $row['bayarnama'] ?? ''
                    ];

                    $this->M_Admin->insertData('Payment', $Data2);
                }
            } catch (\Throwable $th) {
                log_message('error', 'Gagal insert Payment: ' . $th->getMessage());
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function auto_sync_retur_penjualan()
    {
        date_default_timezone_set('Asia/Jakarta');
        $startDate = date('Y-m-d', strtotime('-1 days'));
        $tanggal = date('Y-m-d', strtotime($startDate));

        // 1. Ambil data dari API BangunanAbadi
        $api = new \App\Libraries\ApiBangunanService();
        $response = $api->getData('sr_search', [
            'Filter' => "utamatgl = '$tanggal'",
            'Limit' => 1000
        ]);

        $data = $response['Data'] ?? [];

        // 2. Grouping berdasarkan notransaksi
        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        // 3. Ambil hanya 100 transaksi unik terbaru
        $transaksiTerbaru = $grouped;

        // 4. Loop semua baris (detail) dari tiap transaksi dan masukkan ke database
        foreach ($transaksiTerbaru as $transactionNo => $detailRows) {

            $this->M_Admin->deleteData('retur_penjualan', ['transactionNo' => $transactionNo]);
            foreach ($detailRows as $row) {
                $Data = [
                    'transactionNo'   => $transactionNo,
                    'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                        ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                        : '0000-00-00',

                    'customerNo'        => $row['utamacustomerkode'],
                    'customerName'        => $row['utamacustomernama'],
                    'description'     => $row['utamauraian'] ?? '',
                    'cabang'     => $row['utamanamacabang'] ?? '',
                    'uraian'     => $row['utamauraian'] ?? '',
                    'itemNo'          => $row['detailkodebarang'],
                    'itemName'        => $row['detailnamabarang'],
                    'quantity'        => floatval($row['detailjmlbarang']),
                    'unitPrice'       => floatval($row['detailharga']),
                    'discount'        => floatval($row['detailjmldiskon']),
                    'itemUnitName'    => $row['detailsatuan'],
                    'warehouse'       => $row['utamagudang'],
                    'tax'       => $row['detailpajak1'],
                    'detailNotes'     => $row['detailcatatan']
                ];
                $this->M_Admin->insertData('retur_penjualan', $Data);
            }
        }
        echo "Sync berhasil dijalankan.";
    }

    public function get_pembelian()
    {
        $tanggal = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api   = new \App\Libraries\ApiBangunanService();
        $limit = 1000;
        $page  = 1;
        $allData = [];

        // Loop ambil data per page
        do {
            $response = $api->getData('po_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page
            ]);

            $data = $response['Data'] ?? [];

            // gabungkan data
            $allData = array_merge($allData, $data);

            $page++;
            $hasMore = count($data) >= $limit;
        } while ($hasMore);

        // Group data per transaksi
        $grouped = [];
        foreach ($allData as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $transaksiSemua = $grouped;

        $berhasil = 0;
        $gagal = 0;

        foreach ($transaksiSemua as $transactionNo => $detailRows) {
            try {
                // Hapus transaksi lama
                $this->M_Admin->deleteData('pembelian', ['transactionNo' => $transactionNo]);

                // Insert ulang detail
                foreach ($detailRows as $row) {
                    $Data = [
                        'idNo'            => $row['utamaid'],
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',
                        'vendorNo'        => $row['utamasupplierkode'],
                        'vendorName'      => $row['utamasuppliernama'],
                        'description'     => $row['utamauraian'] ?? '',
                        'termasukpajak'     => $row['utamahargatermasukpajak'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => floatval($row['detailjmlbarang']),
                        'unitPrice'       => floatval($row['detailharga']),
                        'discount'        => floatval($row['detailjmldiskon']),
                        'itemUnitName'    => $row['detailsatuan'],
                        'warehouse'       => $row['utamagudang'],
                        'tax'             => $row['detailpajak1'],
                        'detailNotes'     => $row['detailcatatan']
                    ];

                    $this->M_Admin->insertData('pembelian', $Data);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
            'totalData' => count($allData),
            'totalTransaksi' => count($transaksiSemua)
        ]);
    }


    public function get_penerimaanBarang()
    {
        $tanggal = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api   = new \App\Libraries\ApiBangunanService();
        $limit = 1000;
        $page  = 1;
        $allData = [];

        // Loop ambil data per page
        do {
            $response = $api->getData('grn_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page
            ]);

            $data = $response['Data'] ?? [];

            // gabungkan data
            $allData = array_merge($allData, $data);

            $page++;
            $hasMore = count($data) >= $limit;
        } while ($hasMore);

        // Group data per transaksi
        $grouped = [];
        foreach ($allData as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $transaksiSemua = $grouped;

        $berhasil = 0;
        $gagal = 0;

        foreach ($transaksiSemua as $transactionNo => $detailRows) {
            try {
                // Hapus transaksi lama
                $this->M_Admin->deleteData('penerimaan_barang', ['transactionNo' => $transactionNo]);

                // Insert ulang detail
                foreach ($detailRows as $row) {
                    $Data = [
                        'idNo'            => $row['utamaid'],
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',

                        'vendorNo'        => $row['utamasupplierkode'],
                        'vendorName'      => $row['utamasuppliernama'],
                        'description'     => $row['utamauraian'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => floatval($row['detailjmlbarang']),
                        'unitPrice'       => floatval($row['detailharga']),
                        'discount'        => floatval($row['detailjmldiskon']),
                        'itemUnitName'    => $row['detailsatuan'],
                        'warehouse'       => $row['utamagudang'],
                        'detailNotes'     => $row['detailcatatan'],
                        'detailidpo'      => $row['detailidpo'],
                    ];

                    $this->M_Admin->insertData('penerimaan_barang', $Data);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'berhasil'        => $berhasil,
            'gagal'           => $gagal,
            'totalData'       => count($allData),
            'totalTransaksi'  => count($transaksiSemua),
        ]);
    }


    public function get_invoicePembelian()
    {
        $tanggal = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api   = new \App\Libraries\ApiBangunanService();
        $limit = 1000;
        $page  = 1;
        $allData = [];

        // Loop ambil data per page
        do {
            $response = $api->getData('ri_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page
            ]);

            $data = $response['Data'] ?? [];

            $allData = array_merge($allData, $data);

            $page++;
            $hasMore = count($data) >= $limit;
        } while ($hasMore);

        // Group per transaksi
        $grouped = [];
        foreach ($allData as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $transaksiSemua = $grouped;

        $berhasil = 0;
        $gagal = 0;

        foreach ($transaksiSemua as $transactionNo => $detailRows) {
            try {
                // Hapus transaksi lama
                $this->M_Admin->deleteData('invoice_pembelian', ['transactionNo' => $transactionNo]);

                // Insert ulang detail
                foreach ($detailRows as $row) {
                    $Data = [
                        'idNo'            => $row['utamaid'],
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',

                        'vendorNo'        => $row['utamasupplierkode'],
                        'vendorName'      => $row['utamasuppliernama'],
                        'description'     => $row['utamauraian'] ?? '',
                        'uraian'          => $row['utamauraian'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'termasukpajak'          => $row['utamahargatermasukpajak'] ?? '',
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => floatval($row['detailjmlbarang']),
                        'unitPrice'       => floatval($row['detailharga']),
                        'discount'        => floatval($row['detailjmldiskon']),
                        'itemUnitName'    => $row['detailsatuan'],
                        'tax'             => $row['detailpajak1'],
                        'detailNotes'     => $row['detailcatatan'],
                        'detailidgrn'     => $row['detailidgrn'],
                    ];

                    $this->M_Admin->insertData('invoice_pembelian', $Data);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'berhasil'        => $berhasil,
            'gagal'           => $gagal,
            'totalData'       => count($allData),
            'totalTransaksi'  => count($transaksiSemua),
        ]);
    }


    public function get_returPembelian()
    {
        $tanggal  = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api = new \App\Libraries\ApiBangunanService();
        $allData = [];
        $page    = 1;
        $limit   = 1000;

        // loop ambil data semua halaman
        do {
            $response = $api->getData('prt_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page,
            ]);

            $data = $response['Data'] ?? [];
            $allData = array_merge($allData, $data);

            $hasNext = !empty($data) && count($data) === $limit;
            $page++;
        } while ($hasNext);

        // group by transactionNo
        $grouped = [];
        foreach ($allData as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            $grouped[$noTransaksi][] = $row;
        }

        $berhasil = 0;
        $gagal    = 0;

        foreach ($grouped as $transactionNo => $detailRows) {
            try {
                $this->M_Admin->deleteData('retur_pembelian', ['transactionNo' => $transactionNo]);

                foreach ($detailRows as $row) {
                    $tanggalTransaksi = '0000-00-00';
                    $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']);
                    if ($dt) {
                        $tanggalTransaksi = $dt->format('Y-m-d');
                    }

                    $Data = [
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => $tanggalTransaksi,
                        'vendorNo'        => $row['utamasupplierkode'],
                        'vendorName'      => $row['utamasuppliernama'],
                        'description'     => $row['utamauraian'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => floatval($row['detailjmlbarang']),
                        'unitPrice'       => floatval($row['detailharga']),
                        'discount'        => floatval($row['detailjmldiskon']),
                        'itemUnitName'    => $row['detailsatuan'],
                        'warehouse'       => $row['utamagudang'],
                        'tax'             => $row['detailpajak1'],
                        'termasukpajak'       => $row['utamahargatermasukpajak'],
                        'detailNotes'     => $row['detailcatatan']
                    ];

                    $this->M_Admin->insertData('retur_pembelian', $Data);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                log_message('error', 'Gagal simpan returPembelian: ' . $th->getMessage());
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
        ]);
    }


    public function get_InvoicePenjualan()
    {
        $tanggal  = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api = new \App\Libraries\ApiBangunanService();

        $limit = 1000;

        // === LOOP AMBIL SEMUA INVOICE ===
        $allInvoice = [];
        $page = 1;
        do {
            $response = $api->getData('si_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page,
            ]);
            $data = $response['Data'] ?? [];
            $allInvoice = array_merge($allInvoice, $data);

            $hasNext = !empty($data) && count($data) === $limit;
            $page++;
        } while ($hasNext);

        // === Grouping invoice ===
        $grouped = [];
        foreach ($allInvoice as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            $grouped[$noTransaksi][] = $row;
        }

        $berhasil = 0;
        $gagal    = 0;


        // === SIMPAN INVOICE PENJUALAN DALAM BATCH ===
        $chunks = array_chunk($grouped, 1000, true);
        foreach ($chunks as $i => $chunkGroup) {
            foreach ($chunkGroup as $transactionNo => $detailRows) {
                try {
                    $this->M_Admin->deleteData('invoice_penjualan', ['transactionNo' => $transactionNo]);

                    foreach ($detailRows as $row) {
                        $tanggalTransaksi = '0000-00-00';
                        $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']);
                        if ($dt) {
                            $tanggalTransaksi = $dt->format('Y-m-d');
                        }

                        $Data = [
                            'transactionNo'   => $transactionNo,
                            'transactionDate' => $tanggalTransaksi,
                            'customerNo'      => $row['utamacustomerkode'],
                            'customerName'    => $row['utamacustomernama'],
                            'salesman'        => $row['utamasalesmankode'],
                            'termasukpajak'   => $row['utamahargatermasukpajak'] ?? '',
                            'description'     => $row['utamauraian'] ?? '',
                            'cabang'          => $row['utamanamacabang'] ?? '',
                            'itemNo'          => $row['detailkodebarang'],
                            'itemName'        => $row['detailnamabarang'],
                            'quantity'        =>  $row['detailjmlbarang'],
                            'unitPrice'       => $row['detailharga'],
                            'discount'        => $row['detailjmldiskon'],
                            'itemUnitName'    => $row['detailsatuan'],
                            'warehouse'       => $row['utamagudang'],
                            'tax'             => $row['detailpajak1'],
                            'detailNotes'     => $row['detailcatatan'],
                            'caraBayar'     => $row['utamacarabayarnama'],
                            'total' => $row['utamatotaltransaksi'],

                        ];

                        $this->M_Admin->insertData('invoice_penjualan', $Data);
                    }
                    $berhasil++;
                } catch (\Throwable $th) {
                    log_message('error', 'Gagal simpan invoice_penjualan: ' . $th->getMessage());
                    $gagal++;
                }
            }

            // jeda 1 detik antar batch supaya server gak kaget
            sleep(1);
            log_message('info', "Batch invoice ke-" . ($i + 1) . " selesai, total " . count($chunkGroup) . " transaksi.");
        }
        return $this->response->setJSON([
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
        ]);
    }

    public function get_Payment()
    {
        $tanggal  = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api = new \App\Libraries\ApiBangunanService();

        $limit = 1000;

        // === LOOP AMBIL SEMUA PAYMENT ===
        $allPayment = [];
        $page = 1;
        do {
            $response2 = $api->getData('si_pay_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page,
            ]);
            $data2 = $response2['Data'] ?? [];
            $allPayment = array_merge($allPayment, $data2);

            $hasNext = !empty($data2) && count($data2) === $limit;
            $page++;
        } while ($hasNext);


        // === Grouping payment ===
        $grouped2 = [];
        foreach ($allPayment as $row2) {
            $noTransaksi2 = $row2['utamanotransaksi'];
            $grouped2[$noTransaksi2][] = $row2;
        }

        $berhasil = 0;
        $gagal    = 0;

        // === SIMPAN PAYMENT DALAM BATCH ===
        $chunks2 = array_chunk($grouped2, 1000, true);
        foreach ($chunks2 as $i => $chunkGroup2) {
            foreach ($chunkGroup2 as $transactionNo => $detailRows) {
                try {
                    $this->M_Admin->deleteData('Payment', ['TransactionNo' => $transactionNo]);

                    foreach ($detailRows as $row) {
                        $tanggalTransaksi = '0000-00-00';
                        $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']);
                        if ($dt) {
                            $tanggalTransaksi = $dt->format('Y-m-d');
                        }

                        $Data2 = [
                            'id'              => $row['utamaid'],
                            'TransactionDate' => $tanggalTransaksi,
                            'CustomerID'      => $row['utamakodecustomer'],
                            'CustomerName'    => $row['utamanamacustomer'],
                            'TransactionNo'   => $transactionNo,
                            'BankCode'        => $row['bayarrekbankkode'] ?? '',
                            'Jumlah'        => $row['bayarjumlah'] ?? '',
                            'caraBayar'        => $row['bayarnama'] ?? ''
                        ];

                        $this->M_Admin->insertData('Payment', $Data2);
                    }
                    $berhasil++;
                } catch (\Throwable $th) {
                    log_message('error', 'Gagal simpan Payment: ' . $th->getMessage());
                    $gagal++;
                }
            }

            sleep(1);
            log_message('info', "Batch payment ke-" . ($i + 1) . " selesai, total " . count($chunkGroup2) . " transaksi.");
        }

        return $this->response->setJSON([
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
        ]);
    }



    public function get_ReturPenjualan()
    {
        $tanggal = $this->request->getPost('start_date');
        $tanggal2 = $this->request->getPost('start_date2');

        $api = new \App\Libraries\ApiBangunanService();

        $page = 1;
        $limit = 1000; // aman kalau data banyak
        $allData = [];

        // 🔄 Ambil data per halaman
        do {
            $response = $api->getData('sr_search', [
                'Filter' => "utamatgl between '$tanggal' and '$tanggal2'",
                'Limit'  => $limit,
                'Page'   => $page
            ]);

            $data = $response['Data'] ?? [];
            $allData = array_merge($allData, $data);

            $page++;
        } while (count($data) === $limit); // kalau masih penuh, berarti ada data lanjutan

        // 🚀 Group by No Transaksi
        $grouped = [];
        foreach ($allData as $row) {
            $noTransaksi = $row['utamanotransaksi'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = [];
            }
            $grouped[$noTransaksi][] = $row;
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($grouped as $transactionNo => $detailRows) {
            try {
                // hapus data lama sebelum insert
                $this->M_Admin->deleteData('retur_penjualan', ['transactionNo' => $transactionNo]);

                foreach ($detailRows as $row) {
                    $Data = [
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => (\DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']))
                            ? \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl'])->format('Y-m-d')
                            : '0000-00-00',

                        'customerNo'     => $row['utamacustomerkode'],
                        'customerName'   => $row['utamacustomernama'],
                        'description'    => $row['utamauraian'] ?? '',
                        'cabang'     => $row['utamanamacabang'] ?? '',
                        'uraian'         => $row['utamauraian'] ?? '',
                        'itemNo'         => $row['detailkodebarang'],
                        'itemName'       => $row['detailnamabarang'],
                        'quantity'       => floatval($row['detailjmlbarang']),
                        'unitPrice'      => floatval($row['detailharga']),
                        'discount'       => floatval($row['detailjmldiskon']),
                        'itemUnitName'   => $row['detailsatuan'],
                        'warehouse'      => $row['utamagudang'],
                        'tax'            => $row['detailpajak1'],
                        'termasukpajak'     => $row['utamahargatermasukpajak'] ?? '',
                        'detailNotes'    => $row['detailcatatan'] ?? ''
                    ];

                    $this->M_Admin->insertData('retur_penjualan', $Data);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
        ]);
    }


    public function get_transaksi_list()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('invoice_penjualan', 'transactionDate', $start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'customerNo' => $row['customerNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_PO()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('pembelian', 'transactionDate', $start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'vendorNo' => $row['vendorNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_RI()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataPenerimaanBarangWithPembelian($start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'vendorNo' => $row['vendorNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_PI()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataInvoicePembelian($start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'vendorNo' => $row['vendorNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_SalesReturn()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('retur_penjualan', 'transactionDate', $start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'customerNo' => $row['customerNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_PurchaseReturn()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange('retur_pembelian', 'transactionDate', $start, $end);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'vendorNo' => $row['vendorNo'],
            'transactionDate' => $row['transactionDate']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }


    public function sync_one_sales_return()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getData('retur_penjualan', ['transactionNo' => $transactionNo]);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $customerNo = $header['customerNo'];
        $itemCheck = [];
        $page = 1;

        // Cek & Simpan Customer jika belum ada
        $checkCustomerUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($customerNo) . "&fields=id,customerNo";
        $ch = curl_init($checkCustomerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        $customerExists = false;

        if (!empty($result['d'])) {
            foreach ($this->accurateRecords($result) as $cust) {
                if ($cust['customerNo'] === $customerNo) {
                    $customerExists = true;
                    break;
                }
            }
        }

        if (!$customerExists) {
            $saveCustomer = [
                'name' => $header['customerName'],
                'customerNo' => $customerNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            ];
            $ch = curl_init($accurateHost . "/accurate/api/customer/save.do");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saveCustomer));
            curl_exec($ch);
            curl_close($ch);
        }

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/sales-return/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $checkData = json_decode($response, true);
        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if ($item['number'] === $transactionNo) {
                    $idItem = $item['id'];
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/sales-return/delete.do?id=" . $idItem;
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $deleteResponse = curl_exec($ch);
            curl_close($ch);
        }

        // Cek dan Simpan Item jika belum ada
        foreach ($grouped as $row) {
            $itemNo = $row['itemNo'];
            if (isset($itemCheck[$itemNo])) continue;

            $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
            $ch = curl_init($checkItemUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $itemResp = curl_exec($ch);
            curl_close($ch);
            $itemData = json_decode($itemResp, true);

            $exists = false;
            if (!empty($itemData['d'])) {
                foreach ($this->accurateRecords($itemData) as $i) {
                    if (($i['no'] ?? null) === $itemNo) {
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $newItem = [
                    'name' => $row['itemName'],
                    'no' => $itemNo,
                    'itemType' => 'INVENTORY',
                    'unit1Name' => $row['itemUnitName']
                ];
                $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newItem));
                curl_exec($ch);
                curl_close($ch);
            }

            $itemCheck[$itemNo] = true;
        }

        // Simpan Sales Return baru
        $postData = [
            'number' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            'customerNo' => $header['customerNo'],
            'description' => $header['description'] ?? '',
            'returnType' => 'NO_INVOICE',
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
            ];
        }

        $ch = curl_init($accurateHost . "/accurate/api/sales-return/save.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        log_message('debug', 'Response Accurate: ' . $response);
        log_message('debug', 'HTTP Code: ' . $httpCode);



        $sukses = $this->isAccurateSuccess($responseData, $httpCode);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData, $curlError)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Return"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
    }

    public function sync_one_purchase_return()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getData('retur_pembelian', ['transactionNo' => $transactionNo]);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $vendorNo = $header['vendorNo'];
        $itemCheck = [];
        $page = 1;

        // Cek & Simpan Customer jika belum ada
        $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";

        $ch = curl_init($checkVendorUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $checkVendorResponse = curl_exec($ch);
        curl_close($ch);
        $checkVendorData = json_decode($checkVendorResponse, true);

        $isVendorExist = false;
        if (isset($checkVendorData['s']) && $checkVendorData['s']) {
            foreach ($checkVendorData['d'] as $vendor) {
                if (trim($vendor['vendorNo']) === $vendorNo) {
                    $isVendorExist = true;
                    break;
                }
            }
        }

        if (!$isVendorExist) {
            $postVendor = [
                'name' => $header['vendorName'] ?? $vendorNo,
                'vendorNo' => $vendorNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            ];
            $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postVendor));
            curl_exec($ch);
            curl_close($ch);
        }

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/purchase-return/list.do?keyword=" . urlencode($transactionNo) . "&fields=id,number&sp.page={$page}&sp.pageSize=100";
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $checkData = json_decode($response, true);
        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if ($item['number'] === $transactionNo) {
                    $idItem = $item['id'];
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/purchase-return/delete.do?id=" . $idItem;
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $deleteResponse = curl_exec($ch);
            curl_close($ch);
        }

        // Cek dan Simpan Item jika belum ada
        foreach ($grouped as $row) {
            $itemNo = $row['itemNo'];
            if (isset($itemCheck[$itemNo])) continue;

            $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
            $ch = curl_init($checkItemUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $itemResp = curl_exec($ch);
            curl_close($ch);
            $itemData = json_decode($itemResp, true);

            $exists = false;
            if (!empty($itemData['d'])) {
                foreach ($itemData['d'] as $i) {
                    if ($i['no'] === $itemNo) {
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $newItem = [
                    'name' => $row['itemName'],
                    'no' => $itemNo,
                    'itemType' => 'INVENTORY',
                    'unit1Name' => $row['itemUnitName']
                ];
                $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newItem));
                curl_exec($ch);
                curl_close($ch);
            }

            $itemCheck[$itemNo] = true;
        }

        // Simpan Sales Return baru
        $postData = [
            'number' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            'vendorNo' => $header['vendorNo'],
            'taxable' => ($header['tax'] == 'PPN') ? true : false,
            'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
            'description' => $header['description'] ?? '',
            'returnType' => 'NO_INVOICE',
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'warehouseName' => 'Gudang Pusat',
                'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
            ];
        }

        $ch = curl_init($accurateHost . "/accurate/api/purchase-return/save.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        log_message('debug', 'Response Accurate: ' . $response);
        log_message('debug', 'HTTP Code: ' . $httpCode);



        $sukses = $this->isAccurateSuccess($responseData, $httpCode);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData, $curlError)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Return"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
    }

    public function sync_one_sales_invoice_byNo()
    {
        $transactionNos = $this->request->getPost('transactionNo');
        if (!$transactionNos || !is_array($transactionNos)) {
            return $this->syncErrorResponse('Nomor transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken  = session()->get('access_token');
        $sessionID    = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token atau session tidak ditemukan', count($transactionNos));
        }

        $Username  = session()->get('Username');
        $model     = new M_Admin();
        $logImport = [];
        $itemCheck = [];

        // Ambil semua data berdasarkan banyak transaksi
        $data = $model->getData2('invoice_penjualan', $transactionNos);
        if (!$data) {
            return $this->syncErrorResponse('Data transaksi tidak ditemukan', count($transactionNos));
        }

        // Group data berdasarkan nomor transaksi
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row['transactionNo']][] = $row;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($transactionNos as $transactionNo) {
            if (!isset($groupedData[$transactionNo])) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => 'Data transaksi tidak ditemukan',
                ];
                continue;
            }

            $rows   = $groupedData[$transactionNo];
            $header = $rows[0];
            $customerNo = $header['customerNo'];

            // Cek apakah customer sudah ada
            $checkCustomerUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($customerNo) . "&fields=id,customerNo";
            $custRes = $this->curlGet($checkCustomerUrl, $accessToken, $sessionID);
            $customerExists = false;

            if (!empty($custRes['d'])) {
                foreach ($custRes['d'] as $cust) {
                    if ($cust['customerNo'] === $customerNo) {
                        $customerExists = true;
                        break;
                    }
                }
            }

            // Simpan customer jika belum ada
            if (!$customerExists) {
                $saveCustomer = [
                    'name' => $header['customerName'],
                    'customerNo' => $customerNo,
                    'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
                ];
                $this->curlPost($accurateHost . "/accurate/api/customer/save.do", $accessToken, $sessionID, $saveCustomer);
            }

            // Cek apakah invoice sudah ada
            $checkUrl = $accurateHost . "/accurate/api/sales-invoice/list.do?filter.number.op=EQUAL&filter.number.val=" . urlencode($transactionNo) . "&fields=id,number&sp.pageSize=1";
            $checkData = $this->curlGet($checkUrl, $accessToken, $sessionID);
            $idItem = null;

            if (!empty($checkData['d'])) {
                foreach ($this->accurateRecords($checkData) as $item) {
                    if ($item['number'] === $transactionNo) {
                        $idItem = $item['id'];
                        break;
                    }
                }
            }

            // Jika ada, hapus dulu
            if ($idItem) {
                $deleteUrl = $accurateHost . "/accurate/api/sales-invoice/delete.do?id=" . $idItem;
                $this->curlGet($deleteUrl, $accessToken, $sessionID);
            }

            // Cek & simpan item jika belum ada
            foreach ($rows as $row) {
                $itemNo = $row['itemNo'];
                if (isset($itemCheck[$itemNo])) continue;

                $itemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
                $itemData = $this->curlGet($itemUrl, $accessToken, $sessionID);

                $exists = false;
                if (!empty($itemData['d'])) {
                    foreach ($itemData['d'] as $item) {
                        if ($item['no'] === $itemNo) {
                            $exists = true;
                            break;
                        }
                    }
                }

                if (!$exists) {
                    $newItem = [
                        'name' => $row['itemName'],
                        'no' => $itemNo,
                        'itemType' => 'INVENTORY',
                        'unit1Name' => $row['itemUnitName']
                    ];
                    $this->curlPost($accurateHost . "/accurate/api/item/save.do", $accessToken, $sessionID, $newItem);
                }

                $itemCheck[$itemNo] = true;
            }

            // Simpan ulang sales invoice
            $postData = [
                'number' => $transactionNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'])),
                'customerNo' => $customerNo,
                'taxable' => ($header['tax'] == 'PPN') ? true : false,
                'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
                'description' => $header['description'] ?? '',
                'branchName' =>  $header['cabang'] ?? '',
                'detailItem' => []
            ];

            foreach ($rows as $row) {
                $postData['detailItem'][] = [
                    'itemNo' => $row['itemNo'],
                    'itemName' => $row['itemName'],
                    'quantity' => floatval($row['quantity']),
                    'unitPrice' => floatval($row['unitPrice']),
                    'itemCashDiscount' => floatval($row['discount']),
                    'itemUnitName' => $row['itemUnitName'],
                    'warehouseName' => 'Gudang Pusat',
                    'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
                ];
            }

            $responseData = $this->curlPost($accurateHost . "/accurate/api/sales-invoice/save.do", $accessToken, $sessionID, $postData);
            $sukses = $this->isAccurateSuccess($responseData);

            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => $sukses ? 'Berhasil' : 'Gagal',
                'message' => $sukses ? 'Data berhasil dikirim' : $this->accurateErrorMessage($responseData)
            ];

            if ($sukses) $successCount++;
            else $failCount++;
        }

        // Simpan log
        foreach ($logImport as $log) {
            $this->M_Admin->insertData('log', [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Invoice"
            ]);
        }

        return $this->response->setJSON([
            'status' => ($failCount > 0 ? 'partial' : 'success'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => ($failCount > 0 ? 'Sebagian gagal' : 'Semua berhasil dikirim')
        ]);
    }

    public function sync_one_sales_receipt_byNo()
    {
        $transactionNos = $this->request->getPost('transactionNo');
        if (!$transactionNos || !is_array($transactionNos)) {
            return $this->syncErrorResponse('Nomor transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken  = session()->get('access_token');
        $sessionID    = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token atau session tidak ditemukan', count($transactionNos));
        }

        $Username  = session()->get('Username');
        $model     = new M_Admin();
        $logImport = [];
        $itemCheck = [];

        // Ambil semua data berdasarkan banyak transaksi
        $data = $model->getDataSalesReceipt($transactionNos);
        if (!$data) {
            return $this->syncErrorResponse('Data transaksi tidak ditemukan', count($transactionNos));
        }

        // Group data berdasarkan nomor transaksi
        $groupedData = [];
        foreach ($data as $row) {
            $groupedData[$row['TransactionNo']][] = $row;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($transactionNos as $transactionNo) {
            if (!isset($groupedData[$transactionNo])) {
                $failCount++;
                $logImport[] = [
                    'transactionNo' => $transactionNo,
                    'status' => 'Gagal',
                    'message' => 'Data transaksi tidak ditemukan',
                ];
                continue;
            }

            $rows   = $groupedData[$transactionNo];
            $header = $rows[0];
            $customerNo = $header['CustomerID'];

            // Cek apakah invoice sudah ada
            $prefix = 'SR-' . $transactionNo . '-';

            $checkUrl = $accurateHost . "/accurate/api/sales-receipt/list.do?"
                . "filter.number.op=CONTAIN"
                . "&filter.number.val=" . urlencode($prefix)
                . "&fields=id,number"
                . "&sp.pageSize=100";

            $checkData = $this->curlGet($checkUrl, $accessToken, $sessionID);

            if (!empty($checkData['d'])) {
                foreach ($this->accurateRecords($checkData) as $item) {

                    $deleteUrl = $accurateHost . "/accurate/api/sales-receipt/delete.do?id=" . $item['id'];

                    $this->curlGet($deleteUrl, $accessToken, $sessionID);
                }
            }

            // Simpan ulang sales invoice
            foreach ($rows as $row) {

                $postData = [
                    'number'       => 'SR-' . $row['TransactionNo_Urut'],
                    'transDate'    => date('d/m/Y', strtotime($row['transactionDate'] ?? date('Y-m-d'))),
                    'customerNo'   => $row['CustomerID'],
                    'bankNo'       => $row['BankCode'],
                    'chequeAmount' => $row['Jumlah1'],
                    'branchName'   => $row['cabang'] ?? '',
                    'charField1'   => $row['caraBayar'] ?? '',
                    'detailInvoice' => [
                        [
                            'invoiceNo'     => $row['TransactionNo'],
                            'paymentAmount' => $row['Jumlah1'],
                        ]
                    ]
                ];

                $responseData = $this->curlPost(
                    $accurateHost . "/accurate/api/sales-receipt/save.do",
                    $accessToken,
                    $sessionID,
                    $postData
                );

                $sukses = $this->isAccurateSuccess($responseData);

                $logImport[] = [
                    'transactionNo' => $postData['number'],
                    'status' => $sukses ? 'Berhasil' : 'Gagal',
                    'message' => $sukses ? 'Data berhasil dikirim' : $this->accurateErrorMessage($responseData)
                ];

                if ($sukses) $successCount++;
                else $failCount++;
            }


        }

        // Simpan log satu kali setelah seluruh transaksi diproses.
        foreach ($logImport as $log) {
            $this->M_Admin->insertData('log', [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Receipt"
            ]);
        }

        return $this->response->setJSON([
            'status' => ($failCount > 0 ? 'partial' : 'success'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => ($failCount > 0 ? 'Sebagian gagal' : 'Semua berhasil dikirim')
        ]);
    }

    private function syncErrorResponse(string $message, int $failed = 1)
    {
        return $this->response->setJSON([
            'status' => 'error',
            'berhasil' => 0,
            'gagal' => $failed,
            'message' => $message,
        ]);
    }

    protected function isAccurateSuccess(?array $responseData, ?int $httpCode = null): bool
    {
        if ($httpCode !== null && ($httpCode < 200 || $httpCode >= 300)) {
            return false;
        }

        if ($responseData === null || !array_key_exists('s', $responseData)) {
            return false;
        }

        return filter_var($responseData['s'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }

    protected function accurateErrorMessage(?array $responseData, string $curlError = ''): string
    {
        if ($curlError !== '') {
            return $curlError;
        }

        $details = $responseData['d'] ?? null;

        if (is_array($details)) {
            $messages = [];

            array_walk_recursive($details, static function ($value) use (&$messages): void {
                if (is_scalar($value) && (string) $value !== '') {
                    $messages[] = (string) $value;
                }
            });

            if ($messages !== []) {
                return implode(', ', array_unique($messages));
            }
        }

        if (is_scalar($details) && (string) $details !== '') {
            return (string) $details;
        }

        return 'Respons Accurate tidak valid atau tidak menyatakan berhasil.';
    }

    /**
     * Ambil hanya record berbentuk array dari field data Accurate.
     * Pada respons gagal, field `d` sering berisi string pesan error. Tanpa
     * penyaringan, akses seperti $item['id'] akan memicu fatal error HTTP 500.
     */
    private function accurateRecords($responseData): array
    {
        if (!is_array($responseData)) {
            return [];
        }

        $records = $responseData['d'] ?? [];

        if (!is_array($records)) {
            return [];
        }

        return array_values(array_filter($records, 'is_array'));
    }

    /**
     * Terapkan opsi jaringan yang wajib dipakai oleh seluruh request Accurate.
     *
     * Accurate dapat memindahkan host database dan membalas dengan HTTP 308.
     * Redirect harus diikuti agar request tetap sampai ke host database terbaru.
     */
    private function configureAccurateCurl($ch): void
    {
        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'BangunanAbadi-Accurate-Sync/1.0',
        ];

        // Pertahankan metode dan body POST ketika Accurate mengalihkan host.
        if (defined('CURLOPT_POSTREDIR') && defined('CURL_REDIR_POST_ALL')) {
            $options[CURLOPT_POSTREDIR] = CURL_REDIR_POST_ALL;
        }

        curl_setopt_array($ch, $options);
    }

    private function decodeAccurateResponse($response, int $httpCode, string $curlError): array
    {
        if ($response === false || $curlError !== '') {
            return [
                's' => false,
                'd' => ['Koneksi ke Accurate gagal: ' . ($curlError ?: 'respons kosong')],
                '_http_code' => $httpCode,
            ];
        }

        $data = json_decode($response, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (!is_array($data)) {
            return [
                's' => false,
                'd' => ["Respons Accurate tidak valid (HTTP {$httpCode})."],
                '_http_code' => $httpCode,
            ];
        }

        $data['_http_code'] = $httpCode;

        if ($httpCode < 200 || $httpCode >= 300) {
            $data['s'] = false;

            if (empty($data['d'])) {
                $data['d'] = [in_array($httpCode, [401, 403], true)
                    ? 'Akses Accurate ditolak. Token mungkin kedaluwarsa; silakan hubungkan ulang Accurate.'
                    : "Accurate mengembalikan HTTP {$httpCode}."];
            }
        }

        return $data;
    }

    private function curlGet($url, $accessToken, $sessionID)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return $this->decodeAccurateResponse($response, $httpCode, $curlError);
    }

    private function curlPost($url, $accessToken, $sessionID, $payload)
    {
        log_message('debug', 'Accurate POST URL: ' . $url);
        log_message('debug', 'Accurate POST Payload: ' . json_encode($payload, JSON_PRETTY_PRINT));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        log_message('debug', 'Accurate Response: ' . $response);
        return $this->decodeAccurateResponse($response, $httpCode, $curlError);
    }




    public function get_transaksi_list2()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange2('invoice_penjualan', 'transactionDate', $start, $end, [], ['transactionDate', 'customerNo', 'customerName', 'transactionNo', 'total', 'cabang']);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'customerNo' => $row['customerNo'],
            'transactionDate' => $row['transactionDate'],
            'cabang' => $row['cabang'],
            'total' => $row['total']
        ], $grouped);

        return $this->response->setJSON(array_values($result));
    }

    public function get_transaksi_list3()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        $model = new M_Admin();
        $data = $model->getDataByDateRange2('invoice_penjualan', 'transactionDate', $start, $end, [], ['transactionDate', 'customerNo', 'customerName', 'transactionNo', 'total']);

        $grouped = [];
        foreach ($data as $row) {
            $noTransaksi = $row['transactionNo'];
            if (!isset($grouped[$noTransaksi])) {
                $grouped[$noTransaksi] = $row;
            }
        }

        $result = array_map(fn($row) => [
            'transactionNo' => $row['transactionNo'],
            'customerNo' => $row['customerNo'],
            'transactionDate' => $row['transactionDate'],
            'total' => $row['total']
        ], $grouped);

        return $this->response->setJSON([
            'status' => 'ok',
            'data' => array_map(fn($row) => [
                'number' => $row['transactionNo']
            ], $result)
        ]);
    }

    public function sync_one_sales_receipt()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken  = session()->get('access_token');
        $sessionID    = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = session()->get('Username');
        $model = new M_Admin();

        $data = $model->getSingleTransactionSummary($transactionNo);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        // HAPUS SEMUA SR LAMA (1x SAJA)

        $prefix = 'SR-' . $transactionNo . '-';

        $checkUrl = $accurateHost . "/accurate/api/sales-receipt/list.do?"
            . "filter.number.op=CONTAIN"
            . "&filter.number.val=" . urlencode($prefix)
            . "&fields=id,number"
            . "&sp.pageSize=100";

        $checkData = $this->curlGet($checkUrl, $accessToken, $sessionID);

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                $deleteUrl = $accurateHost . "/accurate/api/sales-receipt/delete.do?id=" . $item['id'];
                $this->curlGet($deleteUrl, $accessToken, $sessionID);
            }
        }

        // 2 INSERT SEMUA PAYMENT

        $successCount = 0;
        $failCount    = 0;
        $logImport    = [];

        foreach ($data as $row) {

            $postData = [
                'number'       => 'SR-' . $row['TransactionNo_Urut'],
                'transDate'    => date('d/m/Y', strtotime($row['transactionDate'] ?? date('Y-m-d'))),
                'customerNo'   => $row['CustomerID'],
                'bankNo'       => $row['BankCode'],
                'chequeAmount' => $row['Jumlah1'],
                'branchName'   => $row['cabang'] ?? '',
                'charField1'   => $row['caraBayar'] ?? '',
                'detailInvoice' => [
                    [
                        'invoiceNo'     => $row['TransactionNo'],
                        'paymentAmount' => $row['Jumlah1']
                    ]
                ]
            ];

            $responseData = $this->curlPost(
                $accurateHost . "/accurate/api/sales-receipt/save.do",
                $accessToken,
                $sessionID,
                $postData
            );

            $sukses = $this->isAccurateSuccess($responseData);

            if ($sukses) {
                $successCount++;
            } else {
                $failCount++;
            }

            // simpan log langsung per row (lebih aman untuk AJAX)
            $this->M_Admin->insertData('log', [
                'TransactionNo'  => $postData['number'],
                'Status'         => $sukses ? 'Berhasil' : 'Gagal',
                'Message'        => $sukses
                    ? 'Data berhasil dikirim'
                    : $this->accurateErrorMessage($responseData),
                'Username'       => $Username,
                'TransactionType' => "Sales_Receipt"
            ]);
        }

        //  RETURN SEKALI SAJA
        $totalDetail = count($data);

        return $this->response->setJSON([
            'status'   => ($failCount > 0 ? 'partial' : 'success'),
            'berhasil' => $successCount,
            'gagal'    => $failCount,
            'total'    => $totalDetail, // tambahin ini
            'message'  => ($failCount > 0 ? 'Sebagian gagal' : 'Semua berhasil')
        ]);
    }


    public function sync_one_sales_invoice()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getData('invoice_penjualan', ['transactionNo' => $transactionNo]);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $customerNo = $header['customerNo'];
        $itemCheck = [];
        $page = 1;

        // Cek & Simpan Customer jika belum ada
        $checkCustomerUrl = $accurateHost . "/accurate/api/customer/list.do?keyword=" . urlencode($customerNo) . "&fields=id,customerNo";
        $ch = curl_init($checkCustomerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        $customerExists = false;

        if (!empty($result['d'])) {
            foreach ($this->accurateRecords($result) as $cust) {
                if ($cust['customerNo'] === $customerNo) {
                    $customerExists = true;
                    break;
                }
            }
        }

        if (!$customerExists) {
            $saveCustomer = [
                'name' => $header['customerName'],
                'customerNo' => $customerNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            ];
            $ch = curl_init($accurateHost . "/accurate/api/customer/save.do");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saveCustomer));
            curl_exec($ch);
            curl_close($ch);
        }

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/sales-invoice/list.do?filter.number.op=EQUAL&filter.number.val=" . urlencode($transactionNo) . "&fields=id,number&sp.pageSize=1";
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $checkData = json_decode($response, true);
        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if ($item['number'] === $transactionNo) {
                    $idItem = $item['id'];
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/sales-invoice/delete.do?id=" . $idItem;
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $deleteResponse = curl_exec($ch);
            curl_close($ch);
        }

        // Cek dan Simpan Item jika belum ada
        foreach ($grouped as $row) {
            $itemNo = $row['itemNo'];
            if (isset($itemCheck[$itemNo])) continue;

            $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
            $ch = curl_init($checkItemUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $itemResp = curl_exec($ch);
            curl_close($ch);
            $itemData = json_decode($itemResp, true);

            $exists = false;
            if (!empty($itemData['d'])) {
                foreach ($itemData['d'] as $i) {
                    if ($i['no'] === $itemNo) {
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $newItem = [
                    'name' => $row['itemName'],
                    'no' => $itemNo,
                    'itemType' => 'INVENTORY',
                    'unit1Name' => $row['itemUnitName']
                ];
                $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newItem));
                curl_exec($ch);
                curl_close($ch);
            }

            $itemCheck[$itemNo] = true;
        }

        // Simpan Sales Invoice baru
        $postData = [
            'number' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'])),
            'customerNo' => $header['customerNo'],
            'taxable' => ($header['tax'] == 'PPN') ? true : false,
            'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
            'description' => $header['description'] ?? '',
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'warehouseName' => 'Gudang Pusat',
                'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? ''
            ];
        }

        $ch = curl_init($accurateHost . "/accurate/api/sales-invoice/save.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        log_message('debug', 'Response Accurate: ' . $response);
        log_message('debug', 'HTTP Code: ' . $httpCode);



        $sukses = $this->isAccurateSuccess($responseData, $httpCode);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData, $curlError)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Sales_Invoice"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
    }

    public function sync_one_purchase_order()
    {
        $transactionNo = $this->request->getPost('transactionNo');

        try {
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getData('pembelian', ['transactionNo' => $transactionNo]);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $vendorNo = $header['vendorNo'];
        $itemCheck = [];
        $page = 1;

        // Cek & Simpan Vendor jika belum ada
        $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";
        $result = $this->curlGet($checkVendorUrl, $accessToken, $sessionID);

        if (!$this->isAccurateSuccess($result)) {
            return $this->syncErrorResponse('Gagal memeriksa vendor: ' . $this->accurateErrorMessage($result));
        }

        $vendorExists = false;

        if (!empty($result['d'])) {
            foreach ($this->accurateRecords($result) as $cust) {
                if (($cust['vendorNo'] ?? null) === $vendorNo) {
                    $vendorExists = true;
                    break;
                }
            }
        }

        if (!$vendorExists) {
            $saveVendor = [
                'name' => $header['vendorName'],
                'vendorNo' => $vendorNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            ];
            $vendorResult = $this->curlPost(
                $accurateHost . "/accurate/api/vendor/save.do",
                $accessToken,
                $sessionID,
                $saveVendor,
            );

            if (!$this->isAccurateSuccess($vendorResult)) {
                return $this->syncErrorResponse('Gagal menyimpan vendor: ' . $this->accurateErrorMessage($vendorResult));
            }
        }

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/purchase-order/list.do?filter.number.op=EQUAL&filter.number.val=" . urlencode($transactionNo) . "&fields=id,number&sp.pageSize=1";
        $checkData = $this->curlGet($checkUrl, $accessToken, $sessionID);

        if (!$this->isAccurateSuccess($checkData)) {
            return $this->syncErrorResponse('Gagal memeriksa Purchase Order: ' . $this->accurateErrorMessage($checkData));
        }

        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if (($item['number'] ?? null) === $transactionNo) {
                    $idItem = $item['id'] ?? null;
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/purchase-order/delete.do?id=" . $idItem;
            $deleteResult = $this->curlGet($deleteUrl, $accessToken, $sessionID);

            if (!$this->isAccurateSuccess($deleteResult)) {
                return $this->syncErrorResponse('Gagal menghapus Purchase Order lama: ' . $this->accurateErrorMessage($deleteResult));
            }
        }

        // Cek dan Simpan Item jika belum ada
        foreach ($grouped as $row) {
            $itemNo = $row['itemNo'];
            if (isset($itemCheck[$itemNo])) continue;

            $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
            $itemData = $this->curlGet($checkItemUrl, $accessToken, $sessionID);

            if (!$this->isAccurateSuccess($itemData)) {
                return $this->syncErrorResponse("Gagal memeriksa item {$itemNo}: " . $this->accurateErrorMessage($itemData));
            }

            $exists = false;
            if (!empty($itemData['d'])) {
                foreach ($itemData['d'] as $i) {
                    if ($i['no'] === $itemNo) {
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $newItem = [
                    'name' => $row['itemName'],
                    'no' => $itemNo,
                    'itemType' => 'INVENTORY',
                    'unit1Name' => $row['itemUnitName']
                ];
                $itemResult = $this->curlPost(
                    $accurateHost . "/accurate/api/item/save.do",
                    $accessToken,
                    $sessionID,
                    $newItem,
                );

                if (!$this->isAccurateSuccess($itemResult)) {
                    return $this->syncErrorResponse("Gagal menyimpan item {$itemNo}: " . $this->accurateErrorMessage($itemResult));
                }
            }

            $itemCheck[$itemNo] = true;
        }

        // Simpan Invoice baru
        $postData = [
            'number' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            'vendorNo' => $vendorNo,
            'description' => $header['description'] ?? '',
            'taxable' => ($header['tax'] == 'PPN') ? true : false,
            'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
            'toAddress' => 'TANGERANG',
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'warehouseName' => 'Gudang Pusat',
                'detailNotes' => isset($row['detailnotes'])
                    ? (string) $row['detailnotes']
                    : (isset($row['detailNotes']) ? (string) $row['detailNotes'] : '')
            ];
        }

        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $responseData = $this->curlPost(
            $accurateHost . "/accurate/api/purchase-order/save.do",
            $accessToken,
            $sessionID,
            $postData,
        );

        $sukses = $this->isAccurateSuccess($responseData);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Order"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
        } catch (\Throwable $exception) {
            log_message('error', "Sync Purchase Order {$transactionNo} gagal: {$exception->getMessage()}");

            return $this->syncErrorResponse($exception->getMessage());
        }
    }

    public function sync_one_receive_item()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getDataPenerimaan($transactionNo);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $vendorNo = $header['vendorNo'];
        $itemCheck = [];
        $page = 1;

        // Cek & Simpan Vendor jika belum ada
        $checkVendorUrl = $accurateHost . "/accurate/api/vendor/list.do?keyword=" . urlencode($vendorNo) . "&fields=id,vendorNo";
        $ch = curl_init($checkVendorUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        $vendorExists = false;

        if (!empty($result['d'])) {
            foreach ($this->accurateRecords($result) as $cust) {
                if ($cust['vendorNo'] === $vendorNo) {
                    $vendorExists = true;
                    break;
                }
            }
        }

        if (!$vendorExists) {
            $saveVendor = [
                'name' => $header['vendorName'],
                'vendorNo' => $vendorNo,
                'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            ];
            $ch = curl_init($accurateHost . "/accurate/api/vendor/save.do");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saveVendor));
            curl_exec($ch);
            curl_close($ch);
        }

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/receive-item/list.do?filter.number.op=EQUAL&filter.number.val=" . urlencode($transactionNo) . "&fields=id,number&sp.pageSize=1";
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $checkData = json_decode($response, true);
        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if ($item['number'] === $transactionNo) {
                    $idItem = $item['id'];
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/receive-item/delete.do?id=" . $idItem;
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $deleteResponse = curl_exec($ch);
            curl_close($ch);
        }

        // Cek dan Simpan Item jika belum ada
        foreach ($grouped as $row) {
            $itemNo = $row['itemNo'];
            if (isset($itemCheck[$itemNo])) continue;

            $checkItemUrl = $accurateHost . "/accurate/api/item/list.do?keyword=" . urlencode($itemNo) . "&fields=id,no";
            $ch = curl_init($checkItemUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $itemResp = curl_exec($ch);
            curl_close($ch);
            $itemData = json_decode($itemResp, true);

            $exists = false;
            if (!empty($itemData['d'])) {
                foreach ($itemData['d'] as $i) {
                    if ($i['no'] === $itemNo) {
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $newItem = [
                    'name' => $row['itemName'],
                    'no' => $itemNo,
                    'itemType' => 'INVENTORY',
                    'unit1Name' => $row['itemUnitName']
                ];
                $ch = curl_init($accurateHost . "/accurate/api/item/save.do");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->configureAccurateCurl($ch);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $accessToken",
                    "X-Session-ID: $sessionID",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newItem));
                curl_exec($ch);
                curl_close($ch);
            }

            $itemCheck[$itemNo] = true;
        }

        // Simpan Sales Invoice baru
        $postData = [
            'number' => $transactionNo,
            'receiveNumber' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            'vendorNo' => $header['vendorNo'],
            'description' => $header['description'] ?? '',
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'warehouseName' => 'Gudang Pusat',
                'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? '',
                'purchaseOrderNumber' => $row['nomorPO'] ?? null
            ];
        }

        $ch = curl_init($accurateHost . "/accurate/api/receive-item/save.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        log_message('debug', 'Response Accurate: ' . $response);
        log_message('debug', 'HTTP Code: ' . $httpCode);



        $sukses = $this->isAccurateSuccess($responseData, $httpCode);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData, $curlError)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Receive_Item"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
    }

    public function sync_one_purchase_invoice()
    {
        $transactionNo = $this->request->getPost('transactionNo');
        if (!$transactionNo) {
            return $this->syncErrorResponse('No transaksi kosong');
        }

        $accurateHost = session()->get('accurate_host');
        $accessToken = session()->get('access_token');
        $sessionID   = session()->get('accurate_session');

        if (!$accurateHost || !$accessToken || !$sessionID) {
            return $this->syncErrorResponse('Token/session tidak ditemukan');
        }

        $Username = $this->session->get('Username');
        $logImport = [];
        $successCount = 0;
        $failCount = 0;

        $model = new M_Admin();
        $data = $model->getDataInvoicePembelian2($transactionNo);

        if (!$data) {
            return $this->syncErrorResponse('Data tidak ditemukan');
        }

        $grouped = [];
        foreach ($data as $row) {
            $grouped[] = $row;
        }

        $header = $grouped[0];
        $vendorNo = $header['vendorNo'];
        $itemCheck = [];
        $page = 1;

        // Cek apakah transaksi sudah ada
        $checkUrl = $accurateHost . "/accurate/api/purchase-invoice/list.do?filter.number.op=EQUAL&filter.number.val=" . urlencode($transactionNo) . "&fields=id,number&sp.pageSize=1";
        $ch = curl_init($checkUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $checkData = json_decode($response, true);
        $idItem = null;

        if (!empty($checkData['d'])) {
            foreach ($this->accurateRecords($checkData) as $item) {
                if ($item['number'] === $transactionNo) {
                    $idItem = $item['id'];
                    break;
                }
            }
        }

        // Jika ada, delete dulu
        if ($idItem) {
            $deleteUrl = $accurateHost . "/accurate/api/purchase-invoice/delete.do?id=" . $idItem;
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->configureAccurateCurl($ch);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $accessToken",
                "X-Session-ID: $sessionID"
            ]);
            $deleteResponse = curl_exec($ch);
            curl_close($ch);
        }


        // Simpan Sales Invoice baru
        $postData = [
            'number' => $transactionNo,
            'billNumber' => $transactionNo,
            'transDate' => date('d/m/Y', strtotime($header['transactionDate'] ?? date('Y-m-d'))),
            'vendorNo' => $header['vendorNo'],
            'description' => $header['description'] ?? '',
            'taxable' => ($header['tax'] == 'PPN') ? true : false,
            'inclusiveTax' => ($header['termasukpajak'] == 1) ? true : false,
            'branchName' =>  $header['cabang'] ?? '',
            'detailItem' => []
        ];

        foreach ($grouped as $row) {
            $postData['detailItem'][] = [
                'itemNo' => $row['itemNo'],
                'itemName' => $row['itemName'],
                'quantity' => floatval($row['quantity']),
                'unitPrice' => floatval($row['unitPrice']),
                'itemCashDiscount' => floatval($row['discount']),
                'itemUnitName' => $row['itemUnitName'],
                'detailNotes' => $row['detailnotes'] ?? $row['detailNotes'] ?? '',
                'receiveItemNumber' => $row['nomorRI'] ?? null
            ];
        }

        $ch = curl_init($accurateHost . "/accurate/api/purchase-invoice/save.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->configureAccurateCurl($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "X-Session-ID: $sessionID",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        log_message('debug', 'Post JSON: ' . json_encode($postData, JSON_PRETTY_PRINT));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        log_message('debug', 'Response Accurate: ' . $response);
        log_message('debug', 'HTTP Code: ' . $httpCode);



        $sukses = $this->isAccurateSuccess($responseData, $httpCode);

        if (!$sukses) {
            $failCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Gagal',
                'message' => $this->accurateErrorMessage($responseData, $curlError)
            ];
        } else {
            $successCount++;
            $logImport[] = [
                'transactionNo' => $transactionNo,
                'status' => 'Berhasil',
                'message' => 'Data berhasil dikirim'
            ];
        }
        // Simpan ke database
        foreach ($logImport as $log) {
            $Data = [
                'TransactionNo' => $log['transactionNo'],
                'Status' => $log['status'],
                'Message' => $log['message'],
                'Username' => $Username,
                'TransactionType' => "Purchase_Invoice"
            ];
            $this->M_Admin->insertData('log', $Data);
        }

        return $this->response->setJSON([
            'status' => ($sukses ? 'success' : 'error'),
            'berhasil' => $successCount,
            'gagal' => $failCount,
            'message' => $logImport[0]['message']
        ]);
    }

    public function get_salesInvoice_no()
    {
        $start_date = $this->request->getPost('start_date');
        $end_date   = $this->request->getPost('end_date');

        if (empty($start_date) || empty($end_date)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Tanggal belum dipilih'
            ]);
        }

        $api = new \App\Libraries\ApiBangunanService();

        $limit = 1000;
        $page  = 1;

        $allData = [];
        do {

            $response = $api->getData('si_search', [
                'Filter' => "utamatgl between '$start_date' and '$end_date'",
                'Limit'  => $limit,
                'Page'   => $page,
                'sort'  => "utamanotransaksi desc",
            ]);
            $data = $response['Data'] ?? [];
            $allData = array_merge($allData, $data);
            $hasNext = !empty($data) && count($data) === $limit;
            $page++;
        } while ($hasNext);

        // ambil unique transaction number
        $uniqueTransactions = [];
        foreach ($allData as $row) {
            $no = $row['utamanotransaksi'] ?? '';
            if ($no != '') {
                $uniqueTransactions[$no] = [
                    'number' => $no
                ];
            }
        }

        // reset index array
        $uniqueTransactions = array_values($uniqueTransactions);
        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $uniqueTransactions
        ]);
    }

    public function get_InvoicePenjualan_no()
    {
        $transactionNos = $this->request->getPost('transactionNo');

        if (empty($transactionNos)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Tidak ada transaksi yang dipilih'
            ]);
        }

        $api = new \App\Libraries\ApiBangunanService();

        $berhasil = 0;
        $gagal = 0;

        foreach ($transactionNos as $transactionNo) {

            try {

                /*
            | SALES INVOICE
            */
                $invoice = $api->getData('si_search', [
                    'Filter' => "utamanotransaksi = '$transactionNo'",
                    'Limit'  => 1000,
                    'Page'   => 1,
                ]);

                $invoiceData = $invoice['Data'] ?? [];

                if (empty($invoiceData)) {
                    $gagal++;
                    continue;
                }

                $this->M_Admin->deleteData('invoice_penjualan', [
                    'transactionNo' => $transactionNo
                ]);

                foreach ($invoiceData as $row) {

                    $dt = DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']);

                    $Data = [
                        'transactionNo'   => $transactionNo,
                        'transactionDate' => $dt ? $dt->format('Y-m-d') : null,
                        'customerNo'      => $row['utamacustomerkode'],
                        'customerName'    => $row['utamacustomernama'],
                        'salesman'        => $row['utamasalesmankode'],
                        'itemNo'          => $row['detailkodebarang'],
                        'itemName'        => $row['detailnamabarang'],
                        'quantity'        => $row['detailjmlbarang'],
                        'unitPrice'       => $row['detailharga'],
                    ];

                    $this->M_Admin->insertData('invoice_penjualan', $Data);
                }


                /*
            | SALES RECEIPT / PAYMENT
            */
                log_message('error', 'Sebelum si_pay_search : ' . $transactionNo);

                $payment = $api->getData('si_pay_search', [
                    'Filter' => "utamanotransaksi = '$transactionNo'",
                    'Limit'  => 1000,
                    'Page'   => 1,
                ]);

                log_message('error', 'Sesudah si_pay_search');
                log_message('error', json_encode($payment));

                $paymentData = $payment['Data'] ?? [];

                $this->M_Admin->deleteData('Payment', [
                    'TransactionNo' => $transactionNo
                ]);

                foreach ($paymentData as $row) {

                    $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $row['utamatgl']);

                    $Data2 = [
                        'id'              => $row['utamaid'],
                        'TransactionDate' => $dt ? $dt->format('Y-m-d') : null,
                        'CustomerID'      => $row['utamakodecustomer'],
                        'CustomerName'    => $row['utamanamacustomer'],
                        'TransactionNo'   => $transactionNo,
                        'BankCode'        => $row['bayarrekbankkode'] ?? '',
                        'Jumlah'          => $row['bayarjumlah'] ?? '',
                        'caraBayar'       => $row['bayarnama'] ?? '',
                    ];

                    $this->M_Admin->insertData('Payment', $Data2);
                }

                $berhasil++;
            } catch (\Throwable $th) {
                log_message('error', $transactionNo . ' : ' . $th->getMessage());
                $gagal++;
            }
        }

        return $this->response->setJSON([
            'status'    => $gagal ? 'partial' : 'success',
            'berhasil' => $berhasil,
            'gagal'    => $gagal,
        ]);
    }
}
