<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityDependency;
use App\Models\ActivityException;
use App\Models\ActivityRecurrence;
use App\Models\FormTemplate;
use App\Models\FormVersion;
use App\Models\User;
use App\Services\ActivityExceptionService;
use App\Services\ActivityRecurrenceService;
use App\Services\FollowUpAutomationService;
use App\Services\SurveyEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase3WorkActivitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_prerequisite_dependency_blocks_activity_start(): void
    {
        $prereq = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'PREREQ-101',
            'activity_type' => 'inspection',
            'title' => 'Initial Site Safety Check',
            'status' => 'pending',
        ]);

        $mainActivity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'MAIN-101',
            'activity_type' => 'maintenance',
            'title' => 'Equipment Repair',
            'status' => 'pending',
        ]);

        ActivityDependency::create([
            'id' => (string) Str::uuid(),
            'activity_id' => $mainActivity->id,
            'prerequisite_activity_id' => $prereq->id,
            'dependency_type' => 'block_start',
        ]);

        // Prerequisite is not completed yet
        $isBlocked = ActivityDependency::where('activity_id', $mainActivity->id)
            ->whereHas('prerequisite', function ($q) {
                $q->where('status', '!=', 'completed');
            })->exists();

        $this->assertTrue($isBlocked);

        // Complete prerequisite
        $prereq->update(['status' => 'completed']);

        $isBlockedAfter = ActivityDependency::where('activity_id', $mainActivity->id)
            ->whereHas('prerequisite', function ($q) {
                $q->where('status', '!=', 'completed');
            })->exists();

        $this->assertFalse($isBlockedAfter);
    }

    public function test_survey_engine_calculates_scores_and_validates_schema(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Surveyor',
            'email' => 'surveyor@bara.app',
            'password' => bcrypt('password'),
        ]);

        $template = FormTemplate::create([
            'id' => (string) Str::uuid(),
            'title' => 'Retail Audit Questionnaire',
            'code' => 'RETAIL-V1',
            'category' => 'merchandising',
        ]);

        $version = FormVersion::create([
            'id' => (string) Str::uuid(),
            'form_template_id' => $template->id,
            'version_number' => 1,
            'schema_definition' => [
                'questions' => [
                    ['id' => 'q1', 'question' => 'Is brand visible?', 'weight' => 2.0, 'correct_answer' => 'yes'],
                    ['id' => 'q2', 'question' => 'Shelf price correct?', 'weight' => 1.0, 'correct_answer' => 'yes'],
                ]
            ],
            'is_published' => true,
        ]);

        $engine = new SurveyEngineService();
        $response = $engine->submitResponse(
            formVersion: $version,
            respondent: $user,
            answers: ['q1' => 'yes', 'q2' => 'no']
        );

        // q1 (2 points) earned out of 3 total points = 66.67%
        $this->assertEquals(66.67, $response->score);
    }

    public function test_activity_recurrence_generates_new_child_instances(): void
    {
        $parent = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'REC-BASE-001',
            'activity_type' => 'call',
            'title' => 'Weekly Client Check-in',
            'planned_start_at' => now(),
            'status' => 'pending',
            'approval_policy' => 'auto',
        ]);

        $recurrence = ActivityRecurrence::create([
            'id' => (string) Str::uuid(),
            'parent_activity_id' => $parent->id,
            'recurrence_pattern' => 'weekly',
            'interval' => 1,
        ]);

        $service = new ActivityRecurrenceService();
        $child = $service->generateNextInstance($recurrence);

        $this->assertNotNull($child);
        $this->assertEquals($parent->id, $child->parent_activity_id);
        $this->assertStringContainsString('(Recurring)', $child->title);
    }

    public function test_exception_service_routes_violations_to_supervisor_queue(): void
    {
        $worker = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Field Rep',
            'email' => 'rep@bara.app',
            'password' => bcrypt('password'),
        ]);

        $supervisor = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Supervisor',
            'email' => 'sup@bara.app',
            'password' => bcrypt('password'),
        ]);

        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'EX-101',
            'activity_type' => 'visit',
            'title' => 'Remote Outlet Visit',
            'status' => 'pending',
        ]);

        $service = new ActivityExceptionService();
        $exception = $service->routeToException(
            activity: $activity,
            user: $worker,
            exceptionType: 'geofence_failed',
            reason: 'GPS signal blocked by indoor structure'
        );

        $this->assertEquals('exception', $activity->fresh()->status);
        $this->assertEquals('pending', $exception->status);

        $service->approveException($exception, $supervisor, 'Verified offline location proof manually.');
        $this->assertEquals('approved', $exception->fresh()->status);
        $this->assertEquals('approved', $activity->fresh()->status);
    }

    public function test_followup_automation_schedules_linked_activity_on_failed_inspection(): void
    {
        $activity = Activity::create([
            'id' => (string) Str::uuid(),
            'reference_no' => 'INSP-501',
            'activity_type' => 'inspection',
            'title' => 'Warehouse Safety Inspection',
            'status' => 'completed',
        ]);

        $automation = new FollowUpAutomationService();
        
        // Survey score below 70% triggers automated follow-up
        $followUp = $automation->evaluateAndScheduleFollowUp($activity, surveyScore: 50.0);

        $this->assertNotNull($followUp);
        $this->assertEquals($activity->id, $followUp->parent_activity_id);
        $this->assertStringContainsString('Failed Inspection 50%', $followUp->title);
    }
}
