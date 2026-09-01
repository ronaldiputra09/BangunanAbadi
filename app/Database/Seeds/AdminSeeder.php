<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = trim((string) getenv('SEED_ADMIN_USERNAME')) ?: 'admin';
        $password = (string) getenv('SEED_ADMIN_PASSWORD');

        if ($password === '') {
            throw new RuntimeException('SEED_ADMIN_PASSWORD wajib diisi sebelum menjalankan AdminSeeder.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('SEED_ADMIN_PASSWORD minimal 12 karakter.');
        }

        $user = [
            'Username' => $username,
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'Active' => 1,
        ];

        $detail = [
            'Username' => $username,
            'ClientID' => $this->nullableEnvironmentValue('SEED_ACCURATE_CLIENT_ID'),
            'ClientSecret' => $this->nullableEnvironmentValue('SEED_ACCURATE_CLIENT_SECRET'),
            'Avatar' => null,
        ];

        $this->db->transException(true)->transStart();

        $existingUser = $this->db->table('Ms_User')
            ->where('Username', $username)
            ->countAllResults() > 0;

        if ($existingUser) {
            $this->db->table('Ms_User')->where('Username', $username)->update($user);
        } else {
            $this->db->table('Ms_User')->insert($user);
        }

        $existingDetail = $this->db->table('Ms_UserDetail')
            ->where('Username', $username)
            ->countAllResults() > 0;

        if ($existingDetail) {
            $this->db->table('Ms_UserDetail')->where('Username', $username)->update($detail);
        } else {
            $this->db->table('Ms_UserDetail')->insert($detail);
        }

        $this->db->transComplete();
    }

    private function nullableEnvironmentValue(string $key): ?string
    {
        $value = trim((string) getenv($key));

        return $value === '' ? null : $value;
    }
}
