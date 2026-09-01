<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionTables extends Migration
{
    public function up(): void
    {
        $this->createPurchaseOrderTable();
        $this->createReceiveItemTable();
        $this->createPurchaseInvoiceTable();
        $this->createPurchaseReturnTable();
        $this->createSalesInvoiceTable();
        $this->createPaymentTable();
        $this->createSalesReturnTable();
    }

    public function down(): void
    {
        foreach ([
            'retur_penjualan',
            'Payment',
            'invoice_penjualan',
            'retur_pembelian',
            'invoice_pembelian',
            'penerimaan_barang',
            'pembelian',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createPurchaseOrderTable(): void
    {
        $fields = $this->transactionFields('vendor');
        $fields['idNo'] = $this->externalId();
        $fields['termasukpajak'] = $this->shortText(20);
        $fields['warehouse'] = $this->shortText();
        $fields['tax'] = $this->shortText(50);

        $this->createTransactionTable('pembelian', $fields, ['idNo', 'vendorNo']);
    }

    private function createReceiveItemTable(): void
    {
        $fields = $this->transactionFields('vendor');
        $fields['idNo'] = $this->externalId();
        $fields['warehouse'] = $this->shortText();
        $fields['detailidgrn'] = $this->externalId();
        $fields['detailidpo'] = $this->externalId();

        $this->createTransactionTable('penerimaan_barang', $fields, ['idNo', 'detailidpo', 'vendorNo']);
    }

    private function createPurchaseInvoiceTable(): void
    {
        $fields = $this->transactionFields('vendor');
        $fields['idNo'] = $this->externalId();
        $fields['uraian'] = ['type' => 'TEXT', 'null' => true];
        $fields['termasukpajak'] = $this->shortText(20);
        $fields['tax'] = $this->shortText(50);
        $fields['detailidgrn'] = $this->externalId();

        $this->createTransactionTable('invoice_pembelian', $fields, ['idNo', 'detailidgrn', 'vendorNo']);
    }

    private function createPurchaseReturnTable(): void
    {
        $fields = $this->transactionFields('vendor');
        $fields['warehouse'] = $this->shortText();
        $fields['tax'] = $this->shortText(50);
        $fields['termasukpajak'] = $this->shortText(20);

        $this->createTransactionTable('retur_pembelian', $fields, ['vendorNo']);
    }

    private function createSalesInvoiceTable(): void
    {
        $fields = $this->transactionFields('customer');
        $fields['salesman'] = $this->shortText();
        $fields['termasukpajak'] = $this->shortText(20);
        $fields['warehouse'] = $this->shortText();
        $fields['tax'] = $this->shortText(50);
        $fields['caraBayar'] = $this->shortText();
        $fields['total'] = $this->decimal();

        $this->createTransactionTable('invoice_penjualan', $fields, ['customerNo']);
    }

    private function createPaymentTable(): void
    {
        $this->forge->addField([
            'row_id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id' => $this->externalId(),
            'TransactionDate' => ['type' => 'DATE', 'null' => true],
            'CustomerID' => $this->shortText(100),
            'CustomerName' => $this->shortText(255),
            'TransactionNo' => ['type' => 'VARCHAR', 'constraint' => 100],
            'BankCode' => $this->shortText(100),
            'Jumlah' => $this->shortText(100),
            'caraBayar' => $this->shortText(),
        ]);
        $this->forge->addKey('row_id', true);
        $this->forge->addKey('id');
        $this->forge->addKey('TransactionNo');
        $this->forge->addKey('TransactionDate');
        $this->forge->addKey('CustomerID');
        $this->forge->createTable('Payment', true);
    }

    private function createSalesReturnTable(): void
    {
        $fields = $this->transactionFields('customer');
        $fields['uraian'] = ['type' => 'TEXT', 'null' => true];
        $fields['warehouse'] = $this->shortText();
        $fields['tax'] = $this->shortText(50);
        $fields['termasukpajak'] = $this->shortText(20);

        $this->createTransactionTable('retur_penjualan', $fields, ['customerNo']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function transactionFields(string $party): array
    {
        $partyNumber = $party . 'No';
        $partyName = $party . 'Name';

        return [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'transactionNo' => ['type' => 'VARCHAR', 'constraint' => 100],
            'transactionDate' => ['type' => 'DATE', 'null' => true],
            $partyNumber => $this->shortText(100),
            $partyName => $this->shortText(255),
            'description' => ['type' => 'TEXT', 'null' => true],
            'cabang' => $this->shortText(),
            'itemNo' => $this->shortText(100),
            'itemName' => $this->shortText(255),
            'quantity' => $this->decimal(),
            'unitPrice' => $this->decimal(),
            'discount' => $this->decimal(),
            'itemUnitName' => $this->shortText(100),
            'detailNotes' => ['type' => 'TEXT', 'null' => true],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @param list<string> $additionalIndexes
     */
    private function createTransactionTable(string $table, array $fields, array $additionalIndexes): void
    {
        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        $this->forge->addKey('transactionNo');
        $this->forge->addKey('transactionDate');

        foreach ($additionalIndexes as $column) {
            $this->forge->addKey($column);
        }

        $this->forge->createTable($table, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function shortText(int $length = 150): array
    {
        return ['type' => 'VARCHAR', 'constraint' => $length, 'null' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function externalId(): array
    {
        return ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function decimal(): array
    {
        return ['type' => 'DECIMAL', 'constraint' => '20,6', 'null' => true];
    }
}
