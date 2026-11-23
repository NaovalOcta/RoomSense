<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IsolatedMemoryTestCase;

class RoomSearchTest extends IsolatedMemoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Room::create(['name' => str_repeat('a', 100), 'building' => 'East', 'capacity' => 50, 'is_active' => true]);
        Room::create(['name' => 'Hidden room', 'building' => 'East', 'capacity' => 50, 'is_active' => false]);
    }

    public static function acceptedLengths(): array
    {
        return array_combine(['BVA-SRC-01', 'BVA-SRC-02', 'BVA-SRC-03', 'BVA-SRC-04', 'BVA-SRC-05'], array_map(fn ($n) => [$n], [0, 1, 50, 99, 100]));
    }

    #[DataProvider('acceptedLengths')]
    public function test_search_accepts_boundary_lengths(int $length): void
    {
        $this->get(route('rooms.index', ['search' => str_repeat('a', $length)]))
            ->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->count() === 1 && $rooms->first()->name === str_repeat('a', 100))->assertDontSee('Hidden room');
    }

    public function test_long_and_non_string_search_are_rejected(): void
    {
        foreach ([str_repeat('a', 101), ['a']] as $value) {
            $this->getJson(route('rooms.index', ['search' => $value]))->assertUnprocessable()->assertJsonValidationErrors('search');
            $this->get(route('rooms.index', ['search' => $value]))->assertUnprocessable()->assertSee('Please correct the search keyword.')->assertDontSee('No rooms found');
        }
    }

    public function test_unicode_whitespace_is_trimmed_before_counting(): void
    {
        $this->get(route('rooms.index', ['search' => "\u{00A0}".str_repeat('a', 100)."\u{3000}"]))
            ->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->count() === 1);
        $this->get(route('rooms.index', ['search' => " \u{00A0} ", 'capacity' => 50]))
            ->assertOk()->assertSee(str_repeat('a', 100))->assertDontSee('Hidden room');
    }

    public function test_search_counts_unicode_codepoints_and_retains_capacity_and_building_filter(): void
    {
        $this->getJson(route('rooms.index', ['search' => str_repeat('😀', 101)]))->assertUnprocessable();
        $this->get(route('rooms.index', ['search' => str_repeat('😀', 100)]))->assertOk();
        $this->getJson(route('rooms.index', ['search' => str_repeat("e\u{0301}", 51)]))->assertUnprocessable();
        $this->get(route('rooms.index', ['search' => 'East', 'capacity' => 50]))->assertOk()->assertSee(str_repeat('a', 100))->assertDontSee('Hidden room');
        $this->get(route('rooms.index', ['search' => 'East', 'capacity' => 100]))->assertOk()->assertSee('No rooms found');
    }
}
