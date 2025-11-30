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

    private string $token = 'test-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.api_token' => $this->token]);
        $this->seed();
    }

    public function test_list_slots_returns_data(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn ($q) => $q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();

        $response = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_list_slots_requires_authentication(): void
    {
        $response = $this->getJson('/api/slots?date=2024-12-15&service_id=1&specialist_id=1');

        $response->assertUnauthorized();
    }

    public function test_list_slots_validates_required_parameters(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/slots');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['date', 'service_id', 'specialist_id']);
    }

    public function test_list_slots_returns_empty_for_incompatible_specialist(): void
    {
        $haircut = Service::where('name', 'Haircut')->first();
        $specialistC = Specialist::where('name', 'Specialist C')->first();

        $response = $this->withToken($this->token)
            ->getJson('/api/slots?date='.Carbon::now()->toDateString()."&service_id={$haircut->id}&specialist_id={$specialistC->id}");

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Specialist does not provide this service']);
    }

    public function test_can_book_and_cancel(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn ($q) => $q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();

        $slotsRes = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");
        $slotsRes->assertOk();
        $slots = $slotsRes->json('data');
        $this->assertNotEmpty($slots);
        $start = Carbon::parse($slots[0]['start_time'])->format('H:i');

        $bookRes = $this->withToken($this->token)
            ->postJson('/api/book', [
                'date' => $date,
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'start_time' => $start,
            ]);
        $bookRes->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'specialist_id', 'service_id', 'start_at', 'end_at', 'canceled']]);
        $appointmentId = $bookRes->json('data.id');

        $cancelRes = $this->withToken($this->token)
            ->deleteJson("/api/appointments/{$appointmentId}");
        $cancelRes->assertOk()->assertJson(['message' => 'Canceled']);
        $this->assertTrue(Appointment::find($appointmentId)->canceled);
    }

    public function test_cannot_double_book_same_slot(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn ($q) => $q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();

        $slotsRes = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");
        $slots = $slotsRes->json('data');
        $start = Carbon::parse($slots[0]['start_time'])->format('H:i');

        $this->withToken($this->token)->postJson('/api/book', [
            'date' => $date,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => $start,
        ])->assertCreated();

        $this->withToken($this->token)->postJson('/api/book', [
            'date' => $date,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => $start,
        ])->assertConflict()
            ->assertJson(['message' => 'Slot is no longer available']);
    }

    public function test_cannot_book_outside_working_hours(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn ($q) => $q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();

        $response = $this->withToken($this->token)->postJson('/api/book', [
            'date' => $date,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => '07:00',
        ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Appointment time is outside working hours']);
    }

    public function test_canceled_slot_becomes_available(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn ($q) => $q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();

        $slotsRes = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");
        $initialCount = count($slotsRes->json('data'));
        $start = Carbon::parse($slotsRes->json('data.0.start_time'))->format('H:i');

        $bookRes = $this->withToken($this->token)->postJson('/api/book', [
            'date' => $date,
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'start_time' => $start,
        ]);
        $appointmentId = $bookRes->json('data.id');

        $slotsAfterBook = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");
        $this->assertLessThan($initialCount, count($slotsAfterBook->json('data')));

        $this->withToken($this->token)->deleteJson("/api/appointments/{$appointmentId}");

        $slotsAfterCancel = $this->withToken($this->token)
            ->getJson("/api/slots?date={$date}&service_id={$service->id}&specialist_id={$specialist->id}");
        $this->assertCount($initialCount, $slotsAfterCancel->json('data'));
    }
}
