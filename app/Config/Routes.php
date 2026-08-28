<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Login::index');  // Untuk halaman utama
$routes->get('login', 'Login::index');
$routes->get('login/register', 'Login::register');
$routes->post('login/proses_register', 'Login::proses_register');
$routes->post('login/proses_login', 'Login::proses_login');
$routes->get('login/proses_login', 'Login::proses_login');


$routes->get('auth', 'Auth::index');
$routes->get('auth/callback', 'Auth::callback');
$routes->post('auth/store_token', 'Auth::store_token');
$routes->get('auth/db-list', 'Auth::dbList');
$routes->post('auth/openDatabase', 'Auth::openDatabase');
$routes->get('auth/openDatabase', 'Auth::openDatabase');
$routes->post('sync_masterpelanggan', 'Auth::sync_masterPelanggan');
$routes->post('sync_masterpemasok', 'Auth::sync_masterPemasok');
$routes->post('sync_masteritem', 'Auth::sync_masterItem');
$routes->post('sync_PurchaseOrder', 'Auth::sync_PurchaseOrder');
$routes->post('sync_ReceiveItem', 'Auth::sync_ReceiveItem');
$routes->post('sync_PurchaseInvoice', 'Auth::sync_PurchaseInvoice');
$routes->post('sync_PurchaseReturn', 'Auth::sync_PurchaseReturn');
$routes->post('sync_SalesInvoice', 'Auth::sync_SalesInvoice');
$routes->post('sync_SalesReturn', 'Auth::sync_SalesReturn');
$routes->post('insert_masterPelanggan', 'Auth::insert_masterPelanggan');
$routes->post('insert_masterItem', 'Auth::insert_masterItem');
$routes->post('auto_sync', 'Auth::auto_sync');
$routes->post('get_pembelian', 'Auth::get_pembelian');
$routes->post('get_penerimaanBarang', 'Auth::get_penerimaanBarang');
$routes->post('get_invoicePembelian', 'Auth::get_invoicePembelian');
$routes->post('get_returPembelian', 'Auth::get_returPembelian');
$routes->post('get_InvoicePenjualan', 'Auth::get_InvoicePenjualan');
$routes->post('get_ReturPenjualan', 'Auth::get_ReturPenjualan');
$routes->post('get_Payment', 'Auth::get_Payment');

$routes->post('get_transaksi_list', 'Auth::get_transaksi_list');
$routes->post('get_transaksi_list2', 'Auth::get_transaksi_list2');
$routes->post('get_transaksi_list3', 'Auth::get_transaksi_list3');
$routes->post('get_transaksi_salesReceipt', 'Auth::get_transaksi_salesReceipt');
$routes->post('get_transaksi_PO', 'Auth::get_transaksi_PO');
$routes->post('get_transaksi_RI', 'Auth::get_transaksi_RI');
$routes->post('get_transaksi_PI', 'Auth::get_transaksi_PI');
$routes->post('get_transaksi_SalesReturn', 'Auth::get_transaksi_SalesReturn');
$routes->post('get_transaksi_PurchaseReturn', 'Auth::get_transaksi_PurchaseReturn');
$routes->post('sync_one_sales_invoice', 'Auth::sync_one_sales_invoice');
$routes->post('sync_one_sales_receipt', 'Auth::sync_one_sales_receipt');
$routes->post('sync_one_sales_invoice_byNo', 'Auth::sync_one_sales_invoice_byNo');
$routes->post('sync_one_sales_receipt_byNo', 'Auth::sync_one_sales_receipt_byNo');
$routes->post('sync_one_purchase_order', 'Auth::sync_one_purchase_order');
$routes->post('sync_one_receive_item', 'Auth::sync_one_receive_item');
$routes->post('sync_one_purchase_invoice', 'Auth::sync_one_purchase_invoice');
$routes->post('sync_one_sales_return', 'Auth::sync_one_sales_return');
$routes->post('sync_one_purchase_return', 'Auth::sync_one_purchase_return');

$routes->get('cron/auto-sync', 'Auth::auto_sync');
$routes->get('cron/auto-sync-pembelian', 'Auth::auto_sync_pembelian');
$routes->get('cron/auto-sync-invoice-pembelian', 'Auth::auto_sync_invoice_pembelian');
$routes->get('cron/auto-sync-retur-pembelian', 'Auth::auto_sync_retur_pembelian');
$routes->get('cron/auto-sync-invoice-penjualan', 'Auth::auto_sync_invoice_penjualan');
$routes->get('cron/auto-sync-retur-penjualan', 'Auth::auto_sync_retur_penjualan');


$routes->get('home', 'Home::index');
$routes->get('home/logout', 'Home::logout');
$routes->get('home/settings', 'Home::settings');
$routes->post('home/simpan_settings', 'Home::simpan_settings');
$routes->get('home/profile', 'Home::profile');
$routes->post('home/proses_new_password', 'Home::proses_new_password');
$routes->post('home/avatar', 'Home::avatar');
$routes->get('home/log', 'Home::log');
$routes->get('DeleteLog', 'Home::deleteLog');
$routes->get('users', 'Home::users');
$routes->get('MasterData', 'Home::migrasi');
$routes->get('delete_user/(:any)', 'Home::deleteUser/$1');
$routes->get('SyncMasterItem', 'Home::sync_master_item');
$routes->get('SyncTransaction', 'Home::sync_transaction');
$routes->get('get-data', 'Home::get_data_manually');
$routes->get('Transaction-no', 'Home::sync_transactionNo');
$routes->get('get-data-no', 'Home::get_data_manuall_no');

$routes->post('get_salesInvoice_no', 'Auth::get_salesInvoice_no');
$routes->post('get_InvoicePenjualan_no', 'Auth::get_InvoicePenjualan_no');
