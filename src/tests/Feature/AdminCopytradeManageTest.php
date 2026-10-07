<?php

namespace Tests\Feature;

use App\Models\AkunCopytrade;
use App\Models\PengaturanMaster;
use App\Models\RiwayatBalanceCopytrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCopytradeManageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $investor;

    protected function setUp(): void
    {
        parent::setUp();

        PengaturanMaster::create([
            'link_referral_broker' => 'https://hfm.com/?ref=YZD',
            'link_master_akun'     => 'https://mql5.com/yzd-master',
            'tier_1_min'           => 100,
            'tier_2_min'           => 5000,
            'tier_3_min'           => 300000,
        ]);

        $this->admin = User::factory()->create(['role' => 'administrator', 'status' => 'active']);
        $this->investor = User::factory()->create(['role' => 'investor', 'status' => 'active']);
    }

    private function makeAccount(string $nomor, string $status = 'pending', string $tier = 'tier_1', float $modal = 1000, array $extra = []): AkunCopytrade
    {
        return AkunCopytrade::create(array_merge([
            'user_id'            => $this->investor->id,
            'tier'               => $tier,
            'broker'             => 'HFM',
            'tipe_akun'          => 'cent',
            'nomor_akun_trading' => $nomor,
            'modal_riil'         => $modal,
            'tanggal_mulai'      => now()->subMonths(2)->toDateString(),
            'nama_bank'          => 'BCA',
            'nomor_rekening'     => '1234567890',
            'atas_nama_rekening' => 'Investor Tester',
            'status'             => $status,
        ], $extra));
    }

    private function makeRecord(AkunCopytrade $akun, string $date, float $balance): void
    {
        RiwayatBalanceCopytrade::create([
            'akun_copytrade_id'            => $akun->id,
            'user_id'                      => $this->investor->id,
            'tanggal_pencatatan'           => $date,
            'balance'                      => $balance,
            'keuntungan_nominal'           => $balance - $akun->modal_riil,
            'keuntungan_persen'            => 0,
            'keuntungan_kumulatif'         => 0,
            'pertumbuhan_kumulatif_persen' => 0,
        ]);
    }

    public function test_verification_tab_defaults_to_pending_newest_first(): void
    {
        $new = $this->makeAccount('NEW-1');
        $old = $this->makeAccount('OLD-1');
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->makeAccount('REJ-1', 'rejected');

        $this->actingAs($this->admin)->get('/admin/copytrade')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Copytrade/Index')
                ->where('activeTab', 'verifikasi')
                ->where('counts.pending', 2)
                ->where('counts.rejected', 1)
                ->has('verifikasi.data', 2)
                ->where('verifikasi.data.0.nomor_akun_trading', 'NEW-1')
                ->where('verifikasi.data.1.nomor_akun_trading', 'OLD-1')
            );
    }

    public function test_verification_tab_can_show_rejected_and_all(): void
    {
        $this->makeAccount('PEN-1');
        $this->makeAccount('REJ-1', 'rejected');

        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=rejected')
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 1)
                ->where('verifikasi.data.0.nomor_akun_trading', 'REJ-1'));

        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=all')
            ->assertInertia(fn ($page) => $page->has('verifikasi.data', 2));
    }

    public function test_list_never_exposes_mt4_passwords(): void
    {
        $this->makeAccount('T3-1', 'pending', 'tier_3', 300000, [
            'server_broker'     => 'XM-Real',
            'master_password'   => 'MasterSecret123',
            'investor_password' => 'InvestorSecret123',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/copytrade');

        $response->assertInertia(fn ($page) => $page
            ->missing('verifikasi.data.0.master_password')
            ->missing('verifikasi.data.0.investor_password'));
        $this->assertStringNotContainsString('MasterSecret123', $response->getContent());
        $this->assertStringNotContainsString('InvestorSecret123', $response->getContent());
    }

    public function test_active_tab_shows_balance_profit_and_recording_flag(): void
    {
        $recorded = $this->makeAccount('ACT-REC', 'active', 'tier_1', 1000);
        $this->makeRecord($recorded, now()->startOfMonth()->toDateString(), 1210);
        $this->makeAccount('ACT-NEW', 'active', 'tier_1', 500);
        $this->makeAccount('PEN-1');

        $this->actingAs($this->admin)->get('/admin/copytrade?tab=aktif&akt_sort_by=nomor_akun_trading&akt_sort_direction=asc')
            ->assertInertia(fn ($page) => $page
                ->where('activeTab', 'aktif')
                ->where('counts.active', 2)
                ->has('aktif.data', 2)
                ->where('aktif.data.0.nomor_akun_trading', 'ACT-NEW')
                ->where('aktif.data.0.current_balance', null)
                ->where('aktif.data.0.recorded_this_month', false)
                ->where('aktif.data.1.nomor_akun_trading', 'ACT-REC')
                ->where('aktif.data.1.current_balance', 1210)
                ->where('aktif.data.1.profit', 210)
                ->where('aktif.data.1.profit_pct', 21)
                ->where('aktif.data.1.recorded_this_month', true)
            );
    }

    public function test_active_tab_filters_accounts_not_recorded_this_month(): void
    {
        $recorded = $this->makeAccount('ACT-REC', 'active');
        $this->makeRecord($recorded, now()->startOfMonth()->toDateString(), 1100);
        $this->makeAccount('ACT-NEW', 'active');

        $this->actingAs($this->admin)->get('/admin/copytrade?tab=aktif&akt_catatan=belum')
            ->assertInertia(fn ($page) => $page
                ->has('aktif.data', 1)
                ->where('aktif.data.0.nomor_akun_trading', 'ACT-NEW'));
    }

    public function test_detail_returns_credentials_for_tier_3_and_history_for_active(): void
    {
        $akun = $this->makeAccount('T3-ACT', 'active', 'tier_3', 300000, [
            'server_broker'     => 'XM-Real',
            'master_password'   => 'MasterSecret123',
            'investor_password' => 'InvestorSecret123',
        ]);
        $this->makeRecord($akun, now()->startOfMonth()->toDateString(), 310000);

        $this->actingAs($this->admin)->getJson("/admin/copytrade/{$akun->id}/detail")
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertJsonPath('credentials.master_password', 'MasterSecret123')
            ->assertJsonPath('credentials.investor_password', 'InvestorSecret123')
            ->assertJsonCount(1, 'history')
            ->assertJsonPath('history.0.balance', 310000);
    }

    public function test_detail_has_no_credentials_for_non_tier_3_and_no_history_for_pending(): void
    {
        $akun = $this->makeAccount('T1-PEN', 'pending', 'tier_1');

        $this->actingAs($this->admin)->getJson("/admin/copytrade/{$akun->id}/detail")
            ->assertOk()
            ->assertJsonPath('credentials', null)
            ->assertJsonCount(0, 'history');
    }

    public function test_investor_cannot_access_admin_copytrade_pages(): void
    {
        $akun = $this->makeAccount('T1-PEN');

        $this->actingAs($this->investor)->get('/admin/copytrade')->assertForbidden();
        $this->actingAs($this->investor)->getJson("/admin/copytrade/{$akun->id}/detail")->assertForbidden();
    }

    public function test_verification_tab_displays_submission_sequence_and_ignores_rejected_accounts(): void
    {
        $investorB = User::factory()->create(['role' => 'investor', 'status' => 'active']);

        // Akun 1: Ditolak (tidak boleh dihitung)
        $rej1 = $this->makeAccount('REJ-1', 'rejected');
        $rej1->forceFill(['created_at' => now()->subDays(10)])->save();

        // Akun 2: Pending (pengajuan ke-1 yang sah)
        $pen1 = $this->makeAccount('PEN-1', 'pending');
        $pen1->forceFill(['created_at' => now()->subDays(5)])->save();

        // Akun 3: Ditolak lagi (tidak boleh dihitung)
        $rej2 = $this->makeAccount('REJ-2', 'rejected');
        $rej2->forceFill(['created_at' => now()->subDays(3)])->save();

        // Akun 4: Pending (pengajuan ke-2 yang sah)
        $pen2 = $this->makeAccount('PEN-2', 'pending');
        $pen2->forceFill(['created_at' => now()->subDay()])->save();

        // Akun Investor B (pengajuan ke-1 untuk Investor B)
        $penB = $this->makeAccount('PEN-B1', 'pending', 'tier_1', 1000, ['user_id' => $investorB->id]);
        $penB->forceFill(['created_at' => now()])->save();

        // Verifikasi tab pending
        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=pending&ver_sort_by=created_at&ver_sort_direction=asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 3)
                ->where('verifikasi.data.0.nomor_akun_trading', 'PEN-1')
                ->where('verifikasi.data.0.pengajuan_ke', 1)
                ->where('verifikasi.data.1.nomor_akun_trading', 'PEN-2')
                ->where('verifikasi.data.1.pengajuan_ke', 2)
                ->where('verifikasi.data.2.nomor_akun_trading', 'PEN-B1')
                ->where('verifikasi.data.2.pengajuan_ke', 1)
            );

        // Verifikasi tab rejected: akun yang ditolak pengajuan_ke bernilai null
        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=rejected&ver_sort_by=created_at&ver_sort_direction=asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 2)
                ->where('verifikasi.data.0.nomor_akun_trading', 'REJ-1')
                ->where('verifikasi.data.0.pengajuan_ke', null)
                ->where('verifikasi.data.1.nomor_akun_trading', 'REJ-2')
                ->where('verifikasi.data.1.pengajuan_ke', null)
            );
    }

    public function test_agreement_display_flag_is_hidden_only_for_second_or_later_submissions_with_prior_approved_account(): void
    {
        // 1. Pengajuan pertama (pending): surat perjanjian wajib tampil
        $pen1 = $this->makeAccount('ACC-1', 'pending');
        $pen1->forceFill(['created_at' => now()->subDays(5)])->save();

        // 2. Pengajuan kedua (pending) sebelum ada akun yang disetujui: surat perjanjian tetap tampil
        $pen2 = $this->makeAccount('ACC-2', 'pending');
        $pen2->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=pending&ver_sort_by=created_at&ver_sort_direction=asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 2)
                ->where('verifikasi.data.0.nomor_akun_trading', 'ACC-1')
                ->where('verifikasi.data.0.pengajuan_ke', 1)
                ->where('verifikasi.data.0.has_approved_account', false)
                ->where('verifikasi.data.0.show_perjanjian', true)
                ->where('verifikasi.data.1.nomor_akun_trading', 'ACC-2')
                ->where('verifikasi.data.1.pengajuan_ke', 2)
                ->where('verifikasi.data.1.has_approved_account', false)
                ->where('verifikasi.data.1.show_perjanjian', true)
            );

        // 3. Admin menyetujui akun pertama (status menjadi active)
        $pen1->update([
            'status'          => 'active',
            'verified_by'     => $this->admin->id,
            'verified_at'     => now(),
            'copy_started_at' => now(),
        ]);

        // 4. Setelah ada akun yang disetujui, akun kedua (pengajuan ke-2) menyembunyikan berkas perjanjian
        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=pending')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 1)
                ->where('verifikasi.data.0.nomor_akun_trading', 'ACC-2')
                ->where('verifikasi.data.0.pengajuan_ke', 2)
                ->where('verifikasi.data.0.has_approved_account', true)
                ->where('verifikasi.data.0.show_perjanjian', false)
            );

        // 5. Investor mengajukan akun ketiga (pengajuan ke-3): berkas perjanjian juga tersembunyi
        $pen3 = $this->makeAccount('ACC-3', 'pending');
        $pen3->forceFill(['created_at' => now()])->save();

        $this->actingAs($this->admin)->get('/admin/copytrade?ver_status=pending&ver_sort_by=created_at&ver_sort_direction=desc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('verifikasi.data', 2)
                ->where('verifikasi.data.0.nomor_akun_trading', 'ACC-3')
                ->where('verifikasi.data.0.pengajuan_ke', 3)
                ->where('verifikasi.data.0.has_approved_account', true)
                ->where('verifikasi.data.0.show_perjanjian', false)
            );
    }
}
