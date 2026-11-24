<?php

namespace Tests\Feature\Module4;

use App\Models\Room;
use App\Models\User;
use Tests\IsolatedMemoryTestCase;

class S1RoomSearchTest extends IsolatedMemoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        foreach ([['Alpha', 30, true], ['Beta', 20, true], ['Hidden', 100, false]] as [$name,$capacity,$active]) {
            Room::create(['name' => $name, 'building' => 'GKB 2', 'capacity' => $capacity, 'is_active' => $active]);
        }
    }

    /** S1-01 */
    public function test_s1_01_default_and_empty(): void
    {
        foreach ([[], ['search' => '']] as $query) {
            $this->get(route('rooms.index', $query))->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->pluck('name')->all() === ['Alpha', 'Beta'] && $rooms->first()->relationLoaded('bookings'));
        }
    }

    /** S1-02 */
    public function test_s1_02_search_and_unicode_whitespace(): void
    {
        $this->get(route('rooms.index', ['search' => "\u{00A0}GKB 2\u{3000}", 'capacity' => 30]))->assertOk()->assertViewHas('searchValue', 'GKB 2')->assertViewHas('rooms', fn ($r) => $r->pluck('name')->all() === ['Alpha']);
        $this->get(route('rooms.index', ['search' => 'Alpha']))->assertOk()->assertViewHas('rooms', fn ($r) => $r->pluck('name')->all() === ['Alpha']);
        $this->get(route('rooms.index', ['search' => 'ZZZ']))->assertOk()->assertViewHas('rooms', fn ($r) => $r->isEmpty());
        $this->get(route('rooms.index', ['search' => str_repeat('é', 100)]))->assertOk();
    }

    /** S1-03 */
    public function test_s1_03_long_search_html(): void
    {
        $s = str_repeat('é', 101);
        $this->get(route('rooms.index', ['search' => $s]))->assertUnprocessable()->assertViewHas('searchValue', $s)->assertViewHas('rooms', fn ($r) => $r->isEmpty())->assertViewHas('searchError');
    }

    /** S1-04 */
    public function test_s1_04_invalid_json(): void
    {
        foreach ([['x'], "\xFF"] as $value) {
            $this->getJson(route('rooms.index', ['search' => $value]))->assertUnprocessable()->assertJsonValidationErrors('search');
        }
    }

    /** S1-05 */
    public function test_s1_05_invalid_html(): void
    {
        $this->get(route('rooms.index', ['search' => ['x']]))->assertUnprocessable()->assertViewHas('searchValue', '')->assertViewHas('rooms', fn ($r) => $r->isEmpty());
    }

    /** S1-06 */
    public function test_s1_06_capacity_and_sqlite_like(): void
    {
        $this->get(route('rooms.index', ['capacity' => 30]))->assertOk()->assertViewHas('rooms', fn ($r) => $r->pluck('name')->all() === ['Alpha']);
        $this->get(route('rooms.index', ['capacity' => 31]))->assertOk()->assertViewHas('rooms', fn ($r) => $r->isEmpty());
        $this->get(route('rooms.index', ['search' => '%']))->assertOk()->assertViewHas('rooms', fn ($r) => $r->count() === 2);
        $this->get(route('rooms.index', ['capacity' => -1]))->assertOk()->assertViewHas('rooms', fn ($r) => $r->count() === 2);
    }
}
