<?php

namespace Tests\Feature\Module4;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\IsolatedMemoryTestCase;

class N2NotificationReadTest extends IsolatedMemoryTestCase
{
    private function notice(User $user)
    {
        return $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'TestNotice', 'data' => ['message' => 'Synthetic notice']]);
    }

    /** N2-01 */
    public function test_n2_01_json_read_and_idempotence(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notice = $this->notice($user);
        $foreign = $this->notice($other);
        $this->actingAs($user)->patchJson(route('notifications.read', $notice->id))->assertOk()->assertJsonPath('count', 0);
        $read = $notice->fresh()->read_at;
        $this->assertNotNull($read);
        $this->travel(2)->minutes();
        $this->patchJson(route('notifications.read', $notice->id))->assertOk()->assertJsonPath('count', 0);
        $this->assertEquals($read, $notice->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
    }

    /** N2-02 */
    public function test_n2_02_html_read_redirects_back(): void
    {
        $user = User::factory()->create();
        $notice = $this->notice($user);
        $this->actingAs($user)->from(route('notifications.index'))->patch(route('notifications.read', $notice->id))->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notice->fresh()->read_at);
    }

    /** N2-03 */
    public function test_n2_03_foreign_and_missing_are_not_modified(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->notice($other);
        $this->actingAs($user)->patchJson(route('notifications.read', $foreign->id))->assertNotFound();
        $this->patchJson(route('notifications.read', (string) Str::uuid()))->assertNotFound();
        $this->assertNull($foreign->fresh()->read_at);
    }
}
