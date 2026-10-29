<?php

namespace App\Console\Commands;

use App\Models\ProfilOrganisasi;
use Illuminate\Console\Command;

class GenerateCertificatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surat:generate-certs {--force : Overwrite existing certificates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate self-signed X.509 digital signature certificates for Simpan.in';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $certsDir = storage_path('app/certs');

        $keyPath  = $certsDir . '/simpanin_key.pem';
        $certPath = $certsDir . '/simpanin_cert.crt';
        $p12Path  = $certsDir . '/simpanin_cert.p12';

        if (file_exists($keyPath) && file_exists($certPath) && !$this->option('force')) {
            $this->warn("Sertifikat sudah tersedia di: {$certsDir}");
            $this->line("Gunakan opsi --force jika ingin membuat ulang sertifikat baru.");
            return self::SUCCESS;
        }

        $this->info("=======================================================");
        $this->info(" Simpan.in — Digital Signature Certificate Generator");
        $this->info("=======================================================\n");

        if (!is_dir($certsDir)) {
            mkdir($certsDir, 0755, true);
            $this->line("[1/4] Direktori dibuat: {$certsDir}");
        } else {
            $this->line("[1/4] Direktori siap: {$certsDir}");
        }

        $profil = ProfilOrganisasi::first();
        $orgName = $profil->nama_instansi ?? 'HIMASE Politeknik';
        $email   = $profil->email ?? 'ketua@himase.ac.id';

        $dn = [
            'countryName'            => 'ID',
            'stateOrProvinceName'    => 'Jawa Barat',
            'localityName'           => 'Sumedang',
            'organizationName'       => $orgName,
            'organizationalUnitName' => 'IT Division',
            'commonName'             => 'Simpan.in Digital Certificate',
            'emailAddress'           => $email,
        ];

        $config = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $passphrase = 'simpanin2026';

        // 2. Private Key
        $this->line("[2/4] Membuat private key RSA-2048...");
        $privateKey = openssl_pkey_new($config);
        if (!$privateKey) {
            $this->error("Gagal membuat private key!");
            while ($msg = openssl_error_string()) {
                $this->error("  Error: {$msg}");
            }
            return self::FAILURE;
        }

        // 3. CSR & Self-signed Cert
        $this->line("[3/4] Membuat CSR dan self-signed certificate (validitas 2 tahun)...");
        $csr  = openssl_csr_new($dn, $privateKey, $config);
        $cert = openssl_csr_sign($csr, null, $privateKey, 730, $config);
        if (!$cert) {
            $this->error("Gagal menandatangani sertifikat!");
            while ($msg = openssl_error_string()) {
                $this->error("  Error: {$msg}");
            }
            return self::FAILURE;
        }

        // 4. Tulis file ke storage
        $this->line("[4/4] Menyimpan berkas ke storage...");
        openssl_pkey_export_to_file($privateKey, $keyPath);
        $this->info("  ✓ Private Key  : {$keyPath}");

        openssl_x509_export_to_file($cert, $certPath);
        $this->info("  ✓ Certificate  : {$certPath}");

        openssl_pkcs12_export_to_file($cert, $p12Path, $privateKey, $passphrase);
        $this->info("  ✓ PKCS#12 (.p12): {$p12Path}");

        $this->newLine();
        $this->info("Sertifikat digital berhasil dibuat dan siap digunakan oleh DigitalSignatureService!");

        return self::SUCCESS;
    }
}
