# Rules 08 - Testing Strategy

## Strategi Testing

Framework: PHPUnit (Feature Tests). Cakupan minimal: happy path untuk setiap endpoint.

---

## Prioritas Testing

### WAJIB Ada Test (Kritikal Bisnis):
1. Kalkulasi komisi afiliasi (tiering berdasarkan modal)
2. Kalkulasi profit sharing (20/25/5/50 persen)
3. Invitation system (token valid, expired, sudah dipakai)
4. Verifikasi investor oleh admin (approve/reject)
5. Alur stepper CopyTrade (3 langkah)
6. Role-based access (admin tidak bisa masuk route investor, dst.)

### Perlu Ada Test (Standard):
- Setiap endpoint baru -- minimal 1 happy path test
- Autentikasi (login, logout, register via invitation)
- Guard: guest redirect ke login

### Tidak Wajib (Cukup Manual QA):
- Tampilan UI / styling Vue
- Animasi / transisi

---

## Penamaan Method Test

Format: test_{actor}_{action}_{expected_result}

Contoh yang benar:
- test_investor_can_view_dashboard
- test_admin_can_verify_investor
- test_guest_is_redirected_to_login
- test_admin_cannot_access_investor_route
- test_commission_tier_premium_above_1m
- test_invitation_token_cannot_be_used_twice
- test_expired_invitation_token_is_rejected

---

## Aturan Penggunaan

- Gunakan trait RefreshDatabase untuk test yang butuh database
- Gunakan actingAs(user) untuk simulate autentikasi
- Gunakan Factory untuk membuat data test -- jangan hardcode ID
- Satu method test = satu skenario yang jelas
- DILARANG menghapus atau menonaktifkan test tanpa konfirmasi
- DILARANG menggunakan markTestSkipped() untuk menyembunyikan gagal

---

## Cara Menjalankan Test

Jalankan semua test:
  php artisan test --compact

Jalankan satu file test:
  php artisan test --compact tests/Feature/InvestorDashboardTest.php

Filter berdasarkan nama method:
  php artisan test --compact --filter=test_commission_tier_premium

Jalankan dengan coverage:
  php artisan test --coverage

---

## Template Dasar Feature Test

Contoh struktur test yang benar:

  class InvestorDashboardTest extends TestCase
  {
      use RefreshDatabase;

      public function test_investor_can_view_dashboard(): void
      {
          // Arrange: siapkan data
           = User::factory()->create(['role' => 'investor']);

          // Act: lakukan aksi
           = ->actingAs()->get(route('investor.dashboard'));

          // Assert: verifikasi hasil
          ->assertOk();
          ->assertInertia(fn () =>
              ->component('Investor/Dashboard')
                   ->has('dashboard')
          );
      }
  }

---

## Test Kalkulasi Bisnis (Contoh Komisi)

Test kalkulasi komisi WAJIB mencakup 3 tier:

  Tier Premium (modal >= 1M):  komisi = 50% dari profit master trader
  Tier Standard (100Jt-1M):   komisi = 40% dari profit master trader
  Tier Basic (< 100Jt):       komisi = 30% dari profit master trader

Dan test edge case:
  - Modal tepat di batas 1M (boundary test)
  - Modal tepat di batas 100Jt (boundary test)
  - Komisi setelah 3 bulan masa berlaku habis (expired)
