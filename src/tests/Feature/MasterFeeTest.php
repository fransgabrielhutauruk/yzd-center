<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMasterFee;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\MasterFeeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MasterFeeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected AkunCopytrade $akun;

    protected BalanceService $balanceService;

    protected MasterFeeService $masterFeeService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::factory()->create([
            'role'      => 'administrator',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->member = User::factory()->create([
            'role'      => 'member',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->akun = AkunCopytrade::create([
            'user_id'            => $this->member->id,
            'tier'               => 'tier_1',
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => 'MT4-TEST-FEE-01',
            'modal_riil'         => 1000.00,
            'tanggal_mulai'      => '2026-08-01',
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1122334455',
            'atas_nama_rekening' => 'Test Member',
            'status'             => 'active',
            'verified_at'        => now(),
        ]);

        PengaturanMasterFee::current();
        $this->balanceService = app(BalanceService::class);
        $this->masterFeeService = app(MasterFeeService::class);
    }

    public function test_profit_bersih_formula_and_automatic_master_fee_generation(): void
    {
        // Rekap 1: Modal $1000 -> Saldo $1200. Profit $200. Fee 30% = $60
        $file = UploadedFile::fake()->image('bukti_saldo_1.png');
        $rekap1 = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'penarikan'          => 0,
            'setoran_tambahan'   => 0,
            'bukti_saldo'        => $file,
        ], $this->member);

        $this->assertEquals(200.00, (float) $rekap1->keuntungan_nominal);
        $this->assertNotNull($rekap1->tagihanMasterFee);
        $this->assertEquals(60.00, (float) $rekap1->tagihanMasterFee->nominal_fee);
        $this->assertEquals('2026-08-01', $rekap1->tagihanMasterFee->periode->format('Y-m-d'));
        $this->assertEquals('2026-09-10', $rekap1->tagihanMasterFee->jatuh_tempo->format('Y-m-d'));
        $this->assertEquals('belum_dibayar', $rekap1->tagihanMasterFee->status);

        // Rekap 2: Ada penarikan $50 dan setoran tambahan $100
        // Saldo sebelumnya $1200, saldo akhir $1400.
        // Profit bersih = 1400 - 1200 + 50 - 100 = 150. Fee 30% = $45
        $file2 = UploadedFile::fake()->image('bukti_saldo_2.png');
        $rekap2 = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-10-01',
            'balance'            => 1400.00,
            'penarikan'          => 50.00,
            'setoran_tambahan'   => 100.00,
            'bukti_saldo'        => $file2,
        ], $this->member);

        $this->assertEquals(150.00, (float) $rekap2->keuntungan_nominal);
        $this->assertNotNull($rekap2->tagihanMasterFee);
        $this->assertEquals(45.00, (float) $rekap2->tagihanMasterFee->nominal_fee);
    }

    public function test_loss_or_break_even_does_not_create_invoice_and_deletes_unpaid_if_recalculated_to_loss(): void
    {
        // Rekap dengan rugi: modal 1000 -> saldo 900. Profit bersih = -100
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 900.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $this->assertEquals(-100.00, (float) $rekap->keuntungan_nominal);
        $this->assertNull($rekap->fresh()->tagihanMasterFee);

        // Ubah menjadi untung $200 (saldo 1200) -> tagihan terbit
        $this->balanceService->updateBalance($rekap, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
        ]);

        $this->assertNotNull($rekap->fresh()->tagihanMasterFee);
        $this->assertEquals(60.00, (float) $rekap->fresh()->tagihanMasterFee->nominal_fee);

        // Ubah kembali menjadi rugi -> tagihan otomatis terhapus
        $this->balanceService->updateBalance($rekap, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 800.00,
        ]);
        $this->assertNull($rekap->fresh()->tagihanMasterFee);
    }

    public function test_recap_is_locked_when_invoice_is_menunggu_verifikasi_or_lunas(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;
        $this->assertNotNull($tagihan);

        // Member unggah bukti transfer -> status menjadi menunggu_verifikasi
        $tfProof = UploadedFile::fake()->image('transfer.jpg');
        $this->masterFeeService->submitProof($tagihan, $tfProof, $this->member);
        $this->assertEquals('menunggu_verifikasi', $tagihan->fresh()->status);

        // Percobaan edit rekap harus ditolak
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rekap terkunci karena tagihan master fee sedang diverifikasi atau sudah lunas.');

        $this->balanceService->updateBalance($rekap, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1300.00,
        ]);
    }

    public function test_admin_can_verify_and_reject_with_reason(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;

        // 1. Submit proof
        $tfProof = UploadedFile::fake()->image('transfer.jpg');
        $this->masterFeeService->submitProof($tagihan, $tfProof, $this->member);

        // 2. Reject
        $this->masterFeeService->reject($tagihan, $this->admin, 'Bukti transfer buram tidak terbaca.');
        $this->assertEquals('ditolak', $tagihan->fresh()->status);
        $this->assertEquals('Bukti transfer buram tidak terbaca.', $tagihan->fresh()->catatan_admin);

        // 3. Re-upload proof
        $tfProof2 = UploadedFile::fake()->image('transfer_clear.jpg');
        $this->masterFeeService->submitProof($tagihan, $tfProof2, $this->member);
        $this->assertEquals('menunggu_verifikasi', $tagihan->fresh()->status);
        $this->assertNull($tagihan->fresh()->catatan_admin);

        // 4. Verify
        $this->masterFeeService->verify($tagihan, $this->admin);
        $this->assertEquals('lunas', $tagihan->fresh()->status);
        $this->assertEquals($this->admin->id, $tagihan->fresh()->verified_by);
        $this->assertNotNull($tagihan->fresh()->verified_at);
    }

    public function test_http_member_and_admin_endpoints(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;

        // Member index
        $res = $this->actingAs($this->member)->get(route('member.master_fee.index'));
        $res->assertOk();

        // Member detail JSON
        $res = $this->actingAs($this->member)->get(route('member.master_fee.show', $tagihan->id));
        $res->assertOk()->assertJsonFragment(['nomor' => $tagihan->nomor]);

        // Another member cannot view detail
        $otherMember = User::factory()->create(['role' => 'member', 'status' => 'active', 'is_active' => true]);
        $this->actingAs($otherMember)->get(route('member.master_fee.show', $tagihan->id))->assertNotFound();

        // Member upload proof
        $tfProof = UploadedFile::fake()->image('transfer.jpg');
        $this->actingAs($this->member)->post(route('member.master_fee.proof', $tagihan->id), [
            'bukti_transfer' => $tfProof,
        ])->assertRedirect();
        $this->assertEquals('menunggu_verifikasi', $tagihan->fresh()->status);

        // Admin endpoints
        $this->actingAs($this->admin)->get(route('admin.master_fee.index'))->assertOk();

        // Admin reject
        $this->actingAs($this->admin)->post(route('admin.master_fee.reject', $tagihan->id), [
            'alasan' => 'Bukti salah akun.',
        ])->assertRedirect();
        $this->assertEquals('ditolak', $tagihan->fresh()->status);

        // Member re-upload & Admin verify
        $this->actingAs($this->member)->post(route('member.master_fee.proof', $tagihan->id), [
            'bukti_transfer' => $tfProof,
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('admin.master_fee.verify', $tagihan->id))->assertRedirect();
        $this->assertEquals('lunas', $tagihan->fresh()->status);

        // Admin update settings
        $this->actingAs($this->admin)->put(route('admin.settings.master_fee.update'), [
            'fee_persen'     => 25,
            'nama_bank'      => 'Mandiri',
            'nomor_rekening' => '9876543210',
            'atas_nama'      => 'Master Fee Updated',
        ])->assertRedirect();
        $this->assertSame(25, PengaturanMasterFee::current()->fee_persen);
        $this->assertEquals('Mandiri', PengaturanMasterFee::current()->nama_bank);
    }

    public function test_document_streaming_authorization(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;
        $tfProof = UploadedFile::fake()->image('transfer.jpg');
        $this->masterFeeService->submitProof($tagihan, $tfProof, $this->member);

        $tagihanId = $tagihan->id;
        $rekapId = $rekap->id;

        // Owner can stream
        $this->actingAs($this->member)->get(route('documents.master_fee_proof', $tagihanId))->assertOk();
        $this->actingAs($this->member)->get(route('documents.balance_proof', $rekapId))->assertOk();

        // Admin can stream
        $this->actingAs($this->admin)->get(route('documents.master_fee_proof', $tagihanId))->assertOk();
        $this->actingAs($this->admin)->get(route('documents.balance_proof', $rekapId))->assertOk();

        // Other member is forbidden (403)
        $otherMember = User::factory()->create(['role' => 'member', 'status' => 'active', 'is_active' => true]);
        $this->actingAs($otherMember)->get(route('documents.master_fee_proof', $tagihanId))->assertForbidden();
        $this->actingAs($otherMember)->get(route('documents.balance_proof', $rekapId))->assertForbidden();
    }

    public function test_send_master_fee_reminders_command_is_idempotent(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;
        $this->assertEquals('2026-09-10', $tagihan->jatuh_tempo->format('Y-m-d'));

        // Simulasikan tanggal 7 (pengingat tgl 7)
        Carbon::setTestNow('2026-09-07 08:00:00');

        $this->artisan('app:send-master-fee-reminders')
            ->expectsOutputToContain('Sebanyak 1 pengingat berhasil dikirimkan.')
            ->assertSuccessful();

        // Jalankan kedua kali di hari yang sama -> harus idempoten (0 pengingat)
        $this->artisan('app:send-master-fee-reminders')
            ->expectsOutputToContain('Sebanyak 0 pengingat berhasil dikirimkan.')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_fee_percent_is_locked_per_invoice(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-09-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member);

        $tagihan = $rekap->fresh()->tagihanMasterFee;
        $this->assertEquals(30.00, (float) $tagihan->fee_persen);
        $this->assertEquals(60.00, (float) $tagihan->nominal_fee);

        // Ubah pengaturan global ke 20%
        $this->masterFeeService->updateSettings(['fee_persen' => 20], $this->admin);

        // Tagihan lama tetap 30% dan fee tetap $60
        $this->assertEquals(30.00, (float) $tagihan->fresh()->fee_persen);
        $this->assertEquals(60.00, (float) $tagihan->fresh()->nominal_fee);
    }

    public function test_month_range_filtering_for_admin_and_member(): void
    {
        $file = UploadedFile::fake()->image('bukti_saldo.png');
        $rekap1 = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-08-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file,
        ], $this->member); // Periode: 2026-07-01

        $file2 = UploadedFile::fake()->image('bukti_saldo2.png');
        $rekap2 = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-10-01',
            'balance'            => 1400.00,
            'bukti_saldo'        => $file2,
        ], $this->member); // Periode: 2026-09-01

        // Filter range 2026-08 s/d 2026-09 -> only rekap2 matches
        $paginator = $this->masterFeeService->paginateForAdmin([
            'periode_dari'   => '2026-08',
            'periode_sampai' => '2026-09',
        ]);
        $this->assertCount(1, $paginator->items());
        $this->assertEquals($rekap2->tagihanMasterFee->id, $paginator->items()[0]->id);

        // Filter range 2026-07 s/d 2026-09 -> both match
        $paginatorAll = $this->masterFeeService->paginateForAdmin([
            'periode_dari'   => '2026-07',
            'periode_sampai' => '2026-09',
        ]);
        $this->assertCount(2, $paginatorAll->items());

        // Controller endpoint filter test
        $res = $this->actingAs($this->admin)->get(route('admin.master_fee.index', [
            'periode_dari'   => '2026-08',
            'periode_sampai' => '2026-09',
        ]));
        $res->assertOk();
    }

    public function test_admin_can_update_master_fee_settings_via_http(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.master_fee.update'), [
            'fee_persen'     => 25,
            'nama_bank'      => 'BCA Syariah',
            'nomor_rekening' => '9988776655',
            'atas_nama'      => 'PT YZD Trading Indonesia',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = $this->masterFeeService->getSettings();
        $this->assertSame(25, $settings->fee_persen);
        $this->assertEquals('BCA Syariah', $settings->nama_bank);
        $this->assertEquals('9988776655', $settings->nomor_rekening);
        $this->assertEquals('PT YZD Trading Indonesia', $settings->atas_nama);
    }

    public function test_member_can_search_master_fee_by_nomor_and_akun(): void
    {
        $file1 = UploadedFile::fake()->image('bukti1.png');
        $rekap1 = $this->balanceService->recordBalance($this->akun, [
            'tanggal_pencatatan' => '2026-08-01',
            'balance'            => 1200.00,
            'bukti_saldo'        => $file1,
        ], $this->member);

        $tagihan1 = $rekap1->fresh()->tagihanMasterFee;

        // Search by invoice number
        $resultNomor = $this->masterFeeService->paginateForMember($this->member, [
            'search' => $tagihan1->nomor,
        ]);
        $this->assertCount(1, $resultNomor->items());
        $this->assertEquals($tagihan1->id, $resultNomor->items()[0]->id);

        // Search by trading account number
        $resultAkun = $this->masterFeeService->paginateForMember($this->member, [
            'search' => 'TEST-FEE-01',
        ]);
        $this->assertCount(1, $resultAkun->items());
        $this->assertEquals($tagihan1->id, $resultAkun->items()[0]->id);

        // Search by broker
        $resultBroker = $this->masterFeeService->paginateForMember($this->member, [
            'search' => 'HFM',
        ]);
        $this->assertCount(1, $resultBroker->items());

        // Search non-existent keyword
        $resultNotFound = $this->masterFeeService->paginateForMember($this->member, [
            'search' => 'NON-EXISTENT-KEYWORD-XYZ',
        ]);
        $this->assertCount(0, $resultNotFound->items());

        // HTTP endpoint test
        $response = $this->actingAs($this->member)->get(route('member.master_fee.index', [
            'search' => $tagihan1->nomor,
        ]));
        $response->assertOk();
    }
}
