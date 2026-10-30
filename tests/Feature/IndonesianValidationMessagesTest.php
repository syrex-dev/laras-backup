<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndonesianValidationMessagesTest extends TestCase
{
    /**
     * Membuktikan pesan validasi bawaan sistem berbahasa Indonesia (lang/id)
     */
    public function test_default_validation_messages_are_in_indonesian(): void
    {
        // Pastikan locale aktif adalah Bahasa Indonesia ('id')
        App::setLocale('id');

        $data = [
            'perihal' => '',
            'admin_email' => 'bukan-email',
            'admin_password' => '123',
        ];

        $rules = [
            'perihal' => 'required',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:6',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails(), 'Validasi harus gagal');

        $errors = $validator->errors();

        // 1. Pesan rule 'required' dalam Bahasa Indonesia
        $perihalError = $errors->first('perihal');
        $this->assertStringContainsString('wajib diisi', $perihalError, "Pesan required harus berbahasa Indonesia ('wajib diisi'), didapat: {$perihalError}");

        // 2. Pesan rule 'email' dalam Bahasa Indonesia
        $emailError = $errors->first('admin_email');
        $this->assertStringContainsString('harus berupa alamat email yang valid', $emailError, "Pesan email harus berbahasa Indonesia, didapat: {$emailError}");

        // 3. Pesan rule 'min' string dalam Bahasa Indonesia
        $passError = $errors->first('admin_password');
        $this->assertStringContainsString('minimal harus memiliki', $passError, "Pesan min harus berbahasa Indonesia, didapat: {$passError}");
    }
}
