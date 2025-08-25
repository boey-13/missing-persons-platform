<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\PointsService;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\ProjectApplication;

class InitializeUserPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:initialize-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize points for existing users based on their activities';

    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        parent::__construct();
        $this->pointsService = $pointsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting user points initialization...');

        $users = User::all();
        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            // Award registration points
            $this->pointsService->awardRegistrationPoints($user);

            // Award points for missing reports
            $missingReports = MissingReport::where('user_id', $user->id)->get();
            foreach ($missingReports as $report) {
                $this->pointsService->awardMissingReportPoints($user, $report->id, $report->full_name);
            }

            // Award points for approved sighting reports
            $sightingReports = SightingReport::where('user_id', $user->id)
                ->where('status', 'approved')
                ->get();
            foreach ($sightingReports as $report) {
                $this->pointsService->awardSightingReportPoints($user, $report->id);
            }

            // Award points for completed community projects
            if ($user->role === 'volunteer') {
                $projectApplications = ProjectApplication::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->with('project')
                    ->get();

                foreach ($projectApplications as $application) {
                    if ($application->project) {
                        $this->pointsService->awardCommunityProjectPoints(
                            $user,
                            $application->project->id,
                            $application->project->title,
                            $application->project->points_reward ?? 0
                        );
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('User points initialization completed!');
    }
}
