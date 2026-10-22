<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_guest_cannot_access_invitations_index(): void
    {
        $response = $this->get(route('admin.invitations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_invitations_index(): void
    {
        $response = $this->actingAs($this->member)
            ->get(route('admin.invitations.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_invitations_index(): void
    {
        Invitation::create([
            'token'          => 'token-member-test-123',
            'label'          => 'Undangan Seminar Member',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.invitations.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Invitations/Index')
                ->has('invitations.data', 1)
            );
    }

    public function test_admin_can_create_invitation(): void
    {
        $payload = [
            'label'          => 'Undangan Batch Webinar',
            'kuota_maksimal' => 10,
            'expired_hours'  => 48,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.invitations.store'), $payload);

        $response->assertRedirect(route('admin.invitations.index'));

        $this->assertDatabaseHas('invitations', [
            'label'          => 'Undangan Batch Webinar',
            'kuota_maksimal' => 10,
        ]);
    }

    public function test_admin_can_delete_single_invitation(): void
    {
        $invitation = Invitation::create([
            'token'          => 'token-delete-single-1',
            'label'          => 'Undangan Single Delete',
            'kuota_maksimal' => 1,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.invitations.destroy', $invitation->invitation_id));

        $response->assertRedirect(route('admin.invitations.index'));

        $this->assertSoftDeleted('invitations', [
            'invitation_id' => $invitation->invitation_id,
        ]);
    }

    public function test_admin_can_batch_delete_invitations(): void
    {
        $inv1 = Invitation::create([
            'token'          => 'token-batch-delete-1',
            'label'          => 'Undangan Batch 1',
            'kuota_maksimal' => 1,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $inv2 = Invitation::create([
            'token'          => 'token-batch-delete-2',
            'label'          => 'Undangan Batch 2',
            'kuota_maksimal' => 2,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $inv3 = Invitation::create([
            'token'          => 'token-batch-keep-3',
            'label'          => 'Undangan Tetap Ada',
            'kuota_maksimal' => 5,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.invitations.batch_destroy'), [
                'ids' => [$inv1->invitation_id, $inv2->invitation_id],
            ]);

        $response->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('invitations', [
            'invitation_id' => $inv1->invitation_id,
        ]);
        $this->assertSoftDeleted('invitations', [
            'invitation_id' => $inv2->invitation_id,
        ]);
        $this->assertNotSoftDeleted('invitations', [
            'invitation_id' => $inv3->invitation_id,
        ]);
    }

    public function test_batch_delete_requires_valid_ids(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.invitations.batch_destroy'), [
                'ids' => [],
            ]);

        $response->assertSessionHasErrors(['ids']);
    }

    public function test_non_admin_cannot_batch_delete_invitations(): void
    {
        $inv = Invitation::create([
            'token'          => 'token-unauthorized-batch',
            'label'          => 'Undangan Unauthorized',
            'kuota_maksimal' => 1,
            'total_terpakai' => 0,
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->member)
            ->delete(route('admin.invitations.batch_destroy'), [
                'ids' => [$inv->invitation_id],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'invitation_id' => $inv->invitation_id,
        ]);
    }
}
