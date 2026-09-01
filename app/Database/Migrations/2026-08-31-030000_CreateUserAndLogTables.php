<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserAndLogTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'Username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'Password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'Active' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'CreatedTime' => ['type' => 'DATETIME', 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('Username', true);
        $this->forge->createTable('Ms_User', true);

        $this->forge->addField([
            'Username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'ClientID' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ClientSecret' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'Avatar' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('Username', true);
        $this->forge->addForeignKey('Username', 'Ms_User', 'Username', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Ms_UserDetail', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'TransactionNo' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'Status' => ['type' => 'VARCHAR', 'constraint' => 30],
            'Message' => ['type' => 'TEXT', 'null' => true],
            'Username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'TransactionType' => ['type' => 'VARCHAR', 'constraint' => 100],
            'Created' => ['type' => 'DATETIME', 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('Username');
        $this->forge->addKey('TransactionNo');
        $this->forge->addKey('Created');
        $this->forge->addForeignKey('Username', 'Ms_User', 'Username', 'CASCADE', 'CASCADE');
        $this->forge->createTable('log', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('log', true);
        $this->forge->dropTable('Ms_UserDetail', true);
        $this->forge->dropTable('Ms_User', true);
    }
}
