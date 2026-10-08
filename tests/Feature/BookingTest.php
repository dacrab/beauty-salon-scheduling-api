<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['salon.api_token' => self::TOKEN]);
        $this->seed();
    }

    public function test_list_slots(): void
    {
        [$service, $specialist] = $this->bookablePair();

        $this->withToken(self::TOKEN)->getJson($this->slotsUrl($service, $specialist))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_list_slots_requires_authentication(): void
    {
        [$service, $specialist] = $this->bookablePair();

        $this->getJson($this->slotsUrl($service, $specialist))->assertUnauthorized();
    }

    public function test_list_slots_validates_required_parameters(): void
    {
        $this->withToken(self::TOKEN)->getJson('/api/slots')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'service_id', 'specialist_id']);
    }

    public function test_list_slots_rejects_specialist_without_service(): void
    {
        [$service] = $this->bookablePair();
        $specialistWithoutHaircut = Specialist::where('name', 'Specialist C')->first();

        $this->withToken(self::TOKEN)->getJson($this->slotsUrl($service, $specialistWithoutHaircut))
            ->assertUnprocessable()
            ->assertJson(['message' => 'Specialist does not provide this service']);
    }

    public function test_can_book_and_cancel(): void
    {
        [$service, $specialist] = $this->bookablePair();
        $start = $this->firstAvailableStart($service, $specialist);

        $bookRes = $this->withToken(self::TOKEN)->postJson('/api/book', [
            'date' => now()->toDateString(),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => $start,
        ]);

        $bookRes->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'specialist_id', 'service_id', 'start_at', 'end_at', 'canceled']]);

        $appointmentId = $bookRes->json('data.id');

        $this->withToken(self::TOKEN)
            ->deleteJson("/api/appointments/{$appointmentId}")
            ->assertOk()
            ->assertJson(['message' => 'Canceled']);
        $this->assertTrue(Appointment::find($appointmentId)->canceled);
    }

    public function test_cannot_double_book_same_slot(): void
    {
        [$service, $specialist] = $this->bookablePair();
        $payload = [
            'date' => now()->toDateString(),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => $this->firstAvailableStart($service, $specialist),
        ];

        $this->withToken(self::TOKEN)->postJson('/api/book', $payload)->assertCreated();
        $this->withToken(self::TOKEN)->postJson('/api/book', $payload)
            ->assertConflict()
            ->assertJson(['message' => 'Slot is no longer available']);
    }

    public function test_cannot_book_outside_working_hours(): void
    {
        [$service, $specialist] = $this->bookablePair();

        $this->withToken(self::TOKEN)->postJson('/api/book', [
            'date' => now()->toDateString(),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => '07:00',
        ])->assertUnprocessable()
            ->assertJson(['message' => 'Appointment time is outside working hours']);
    }

    public function test_canceled_slot_becomes_available(): void
    {
        [$service, $specialist] = $this->bookablePair();
        $initialSlots = $this->listSlots($service, $specialist);

        $appointmentId = $this->withToken(self::TOKEN)->postJson('/api/book', [
            'date' => now()->toDateString(),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => Carbon::parse($initialSlots[0]['start_time'])->format('H:i'),
        ])->json('data.id');

        $this->assertLessThan(count($initialSlots), count($this->listSlots($service, $specialist)));

        $this->withToken(self::TOKEN)->deleteJson("/api/appointments/{$appointmentId}");

        $this->assertCount(count($initialSlots), $this->listSlots($service, $specialist));
    }

    private function bookablePair(): array
    {
        return [
            Service::where('name', 'Haircut')->first(),
            Specialist::where('name', 'Specialist A')->first(),
        ];
    }

    private function slotsUrl(Service $service, Specialist $specialist): string
    {
        return '/api/slots?'.http_build_query([
            'date' => now()->toDateString(),
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
        ]);
    }

    private function listSlots(Service $service, Specialist $specialist): array
    {
        $response = $this->withToken(self::TOKEN)->getJson($this->slotsUrl($service, $specialist));
        $response->assertOk();

        return $response->json('data');
    }

    private function firstAvailableStart(Service $service, Specialist $specialist): string
    {
        $slots = $this->listSlots($service, $specialist);
        $this->assertNotEmpty($slots);

        return Carbon::parse($slots[0]['start_time'])->format('H:i');
    }
}
