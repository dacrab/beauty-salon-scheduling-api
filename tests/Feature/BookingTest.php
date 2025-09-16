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

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.api_token' => 'test-token']);
        $this->seed();
    }

    public function test_list_slots_returns_data(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn($q)=>$q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();
        $res = $this->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/slots?date='.$date.'&service_id='.$service->id.'&specialist_id='.$specialist->id);
        $res->assertOk()->assertJsonStructure(['data']);
    }

    public function test_can_book_and_cancel(): void
    {
        $service = Service::first();
        $specialist = Specialist::whereHas('services', fn($q)=>$q->whereKey($service->id))->first();
        $date = Carbon::now()->toDateString();
        // pick the first available slot to avoid conflicts with seeded data
        $slotsRes = $this->withHeader('Authorization', 'Bearer test-token')
            ->getJson('/api/slots?date='.$date.'&service_id='.$service->id.'&specialist_id='.$specialist->id);
        $slotsRes->assertOk();
        $slots = $slotsRes->json('data');
        $this->assertNotEmpty($slots);
        $start = Carbon::parse($slots[0]['start_time'])->format('H:i');

        $res = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/book', [
                'date' => $date,
                'service_id' => $service->id,
                'specialist_id' => $specialist->id,
                'start_time' => $start,
            ]);
        $res->assertCreated();
        $appointmentId = $res->json('data.id');
        $this->assertNotNull($appointmentId);

        $cancelRes = $this->withHeader('Authorization', 'Bearer test-token')
            ->deleteJson('/api/appointments/'.$appointmentId);
        $cancelRes->assertOk();
        $this->assertTrue(Appointment::find($appointmentId)->canceled);
    }
}


