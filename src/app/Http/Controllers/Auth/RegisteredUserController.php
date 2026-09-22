<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\KontenLegal;
use App\Services\AgreementTemplateService;
use App\Services\AuthService;
use App\Services\InvitationService;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly InvitationService $invitationService,
    ) {}

    /**
     * Tampilkan formulir pendaftaran tertutup via token undangan.
     */
    public function create(Request $request): Response
    {
        $token = $request->query('invite');
        $invitationStatus = $this->invitationService->validateToken($token);
        $terms = KontenLegal::getTerms();
        $privacy = KontenLegal::getPrivacyPolicy();

        return Inertia::render('Auth/Register', [
            'invitation' => $invitationStatus,
            'token'      => $token,
            'terms'      => $terms ? [
                'judul'  => $terms->judul,
                'konten' => $terms->konten,
            ] : null,
            'privacy' => $privacy ? [
                'judul'  => $privacy->judul,
                'konten' => $privacy->konten,
            ] : null,
        ]);
    }

    /**
     * Proses pendaftaran pengguna baru.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $token = $request->string('invite_token')->value();
        $ip = $request->ip() ?? '127.0.0.1';

        $user = $this->authService->registerWithInvitation($validated, $token, $ip);

        return redirect()->route('login')
            ->with('registration_success', [
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ]);
    }

    /**
     * Unduh atau hasilkan template surat perjanjian resmi sesuai tier investasi
     * dan data formulir pendaftaran.
     */
    public function downloadTemplate(Request $request, string $tier, AgreementTemplateService $templateService): BinaryFileResponse
    {
        $validTiers = ['tier_1', 'tier_2', 'tier_3'];

        abort_unless(in_array($tier, $validTiers, true), 404);

        $data = $request->all();
        $result = $templateService->generate($tier, $data);

        return response()->download($result['path'], $result['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Periksa keabsahan kode referral affiliator secara real-time pada formulir registrasi.
     */
    public function checkReferral(Request $request, ReferralService $referralService): \Illuminate\Http\JsonResponse
    {
        $code = $request->query('code');
        $affiliator = $referralService->validateReferralCode($code);

        if (!$affiliator) {
            return response()->json([
                'valid'   => false,
                'message' => 'Kode referral tidak ditemukan.',
            ], 422);
        }

        return response()->json([
            'valid'      => true,
            'affiliator' => [
                'name'          => $affiliator->name,
                'referral_code' => $affiliator->referral_code,
            ],
        ]);
    }
}
