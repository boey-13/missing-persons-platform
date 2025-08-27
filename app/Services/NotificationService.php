<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\MissingReport;
use App\Models\SightingReport;
use App\Models\CommunityProject;
use App\Models\ProjectApplication;
use App\Models\VolunteerApplication;

class NotificationService
{
    /**
     * Send notification to a user
     */
    public static function send($userId, $type, $title, $message, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Missing Person Report Notifications
     */
    public static function missingReportSubmitted($report)
    {
        self::send(
            $report->user_id,
            'missing_report_submitted',
            'Missing Person Report Submitted',
            "Your missing person report for {$report->full_name} has been submitted and is under review.",
            [
                'action' => 'view_report',
                'report_id' => $report->id,
                'person_name' => $report->full_name
            ]
        );
    }

    public static function missingReportApproved($report)
    {
        self::send(
            $report->user_id,
            'missing_report_approved',
            'Missing Person Report Approved',
            "Your missing person report for {$report->full_name} has been approved and is now visible to the public.",
            [
                'action' => 'view_report',
                'report_id' => $report->id,
                'person_name' => $report->full_name
            ]
        );
    }

    public static function missingReportRejected($report, $reason)
    {
        self::send(
            $report->user_id,
            'missing_report_rejected',
            'Missing Person Report Rejected',
            "Your missing person report for {$report->full_name} has been rejected. Please review the reason and resubmit.",
            [
                'action' => 'resubmit_report',
                'report_id' => $report->id,
                'person_name' => $report->full_name,
                'reason' => $reason
            ]
        );
    }

    public static function missingPersonFound($report)
    {
        self::send(
            $report->user_id,
            'missing_person_found',
            'Missing Person Found!',
            "Great news! {$report->full_name} has been found safe.",
            [
                'action' => 'view_report',
                'report_id' => $report->id,
                'person_name' => $report->full_name
            ]
        );
    }

    /**
     * Sighting Report Notifications
     */
    public static function sightingReportSubmitted($sighting)
    {
        // Only send notification if user is logged in
        if ($sighting->user_id) {
            self::send(
                $sighting->user_id,
                'sighting_report_submitted',
                'Sighting Report Submitted',
                "Your sighting report has been submitted and is under review.",
                [
                    'action' => 'view_sighting',
                    'sighting_id' => $sighting->id,
                    'location' => $sighting->location
                ]
            );
        }
    }

    public static function sightingReportApproved($sighting)
    {
        self::send(
            $sighting->user_id,
            'sighting_report_approved',
            'Sighting Report Approved',
            "Your sighting report has been approved and shared with authorities.",
            [
                'action' => 'view_sighting',
                'sighting_id' => $sighting->id,
                'location' => $sighting->location
            ]
        );
    }

    public static function sightingReportRejected($sighting, $reason)
    {
        self::send(
            $sighting->user_id,
            'sighting_report_rejected',
            'Sighting Report Rejected',
            "Your sighting report has been rejected. Please review the reason and resubmit if needed.",
            [
                'action' => 'resubmit_sighting',
                'sighting_id' => $sighting->id,
                'location' => $sighting->location,
                'reason' => $reason
            ]
        );
    }

    /**
     * Community Project Notifications
     */
    public static function projectCreated($project)
    {
        // Notify all volunteers about new project
        $volunteers = User::where('role', 'volunteer')->get();
        foreach ($volunteers as $volunteer) {
            self::send(
                $volunteer->id,
                'new_project_available',
                'New Community Project Available',
                "A new community project '{$project->title}' is now available for volunteers.",
                [
                    'action' => 'view_project',
                    'project_id' => $project->id,
                    'project_title' => $project->title,
                    'location' => $project->location
                ]
            );
        }
    }

    public static function projectApplicationSubmitted($application)
    {
        self::send(
            $application->user_id,
            'project_application_submitted',
            'Project Application Submitted',
            "Your application for '{$application->project->title}' has been submitted and is under review.",
            [
                'action' => 'view_application',
                'application_id' => $application->id,
                'project_title' => $application->project->title
            ]
        );
    }

    public static function projectApplicationApproved($application)
    {
        self::send(
            $application->user_id,
            'project_application_approved',
            'Project Application Approved',
            "Your application for '{$application->project->title}' has been approved! You can now participate in this project.",
            [
                'action' => 'view_project',
                'application_id' => $application->id,
                'project_id' => $application->project_id,
                'project_title' => $application->project->title
            ]
        );
    }

    public static function projectApplicationRejected($application, $reason)
    {
        self::send(
            $application->user_id,
            'project_application_rejected',
            'Project Application Rejected',
            "Your application for '{$application->project->title}' has been rejected. Please review the reason and reapply if needed.",
            [
                'action' => 'reapply_project',
                'application_id' => $application->id,
                'project_id' => $application->project_id,
                'project_title' => $application->project->title,
                'reason' => $reason
            ]
        );
    }

    public static function projectCompleted($project)
    {
        // Notify all participants
        $participants = $project->applications()->where('status', 'approved')->get();
        foreach ($participants as $application) {
            self::send(
                $application->user_id,
                'project_completed',
                'Project Completed',
                "The community project '{$project->title}' has been completed. You've earned {$project->points_reward} points!",
                [
                    'action' => 'view_project',
                    'project_id' => $project->id,
                    'project_title' => $project->title,
                    'points_earned' => $project->points_reward
                ]
            );
        }
    }

    /**
     * Volunteer Application Notifications
     */
    public static function volunteerApplicationSubmitted($application)
    {
        self::send(
            $application->user_id,
            'volunteer_application_submitted',
            'Volunteer Application Submitted',
            "Your volunteer application has been submitted and is under review.",
            [
                'action' => 'view_application',
                'application_id' => $application->id
            ]
        );
    }

    public static function volunteerApplicationApproved($application)
    {
        self::send(
            $application->user_id,
            'volunteer_application_approved',
            'Volunteer Application Approved',
            "Congratulations! Your volunteer application has been approved. You can now participate in community projects.",
            [
                'action' => 'open_projects',
                'application_id' => $application->id
            ]
        );
    }

    public static function volunteerApplicationRejected($application, $reason)
    {
        self::send(
            $application->user_id,
            'volunteer_application_rejected',
            'Volunteer Application Rejected',
            "Your volunteer application has been rejected. Please review the reason and reapply if needed.",
            [
                'action' => 'reapply',
                'application_id' => $application->id,
                'reason' => $reason
            ]
        );
    }

    /**
     * Points System Notifications
     */
    public static function pointsEarned($userId, $points, $reason, $action = null)
    {
        self::send(
            $userId,
            'points_earned',
            'Points Earned!',
            "You've earned {$points} points for {$reason}.",
            [
                'action' => $action,
                'points_earned' => $points,
                'reason' => $reason
            ]
        );
    }

    public static function milestoneReached($userId, $milestone, $points)
    {
        self::send(
            $userId,
            'milestone_reached',
            'Milestone Reached!',
            "Congratulations! You've reached the {$milestone} milestone with {$points} total points.",
            [
                'action' => 'view_profile',
                'milestone' => $milestone,
                'total_points' => $points
            ]
        );
    }

    /**
     * System Notifications
     */
    public static function welcomeNewUser($user)
    {
        self::send(
            $user->id,
            'welcome',
            'Welcome to Missing Persons Platform!',
            "Welcome {$user->name}! Thank you for joining our community. Start by reporting a missing person or volunteering for community projects.",
            [
                'action' => 'get_started',
                'user_name' => $user->name
            ]
        );
    }

    public static function dailyLogin($userId)
    {
        self::send(
            $userId,
            'daily_login',
            'Daily Login Bonus',
            "Welcome back! You've earned 1 point for your daily login.",
            [
                'action' => 'view_profile',
                'points_earned' => 1,
                'reason' => 'Daily login'
            ]
        );
    }

    public static function socialShareBonus($userId, $personName)
    {
        self::send(
            $userId,
            'social_share',
            'Social Share Bonus',
            "Thank you for sharing the missing person case for {$personName}! You've earned 5 points.",
            [
                'action' => 'view_profile',
                'points_earned' => 5,
                'reason' => 'Social media share',
                'person_name' => $personName
            ]
        );
    }

    /**
     * Admin Notifications
     */
    public static function newMissingReportForAdmin($report)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            self::send(
                $admin->id,
                'new_missing_report',
                'New Missing Person Report',
                "A new missing person report for {$report->full_name} requires your review.",
                [
                    'action' => 'review_report',
                    'report_id' => $report->id,
                    'person_name' => $report->full_name,
                    'reporter' => $report->user->name
                ]
            );
        }
    }

    public static function newSightingReportForAdmin($sighting)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            self::send(
                $admin->id,
                'new_sighting_report',
                'New Sighting Report',
                "A new sighting report requires your review.",
                [
                    'action' => 'review_sighting',
                    'sighting_id' => $sighting->id,
                    'location' => $sighting->location,
                    'reporter' => $sighting->user ? $sighting->user->name : $sighting->reporter_name
                ]
            );
        }
    }

    public static function newVolunteerApplicationForAdmin($application)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            self::send(
                $admin->id,
                'new_volunteer_application',
                'New Volunteer Application',
                "A new volunteer application from {$application->user->name} requires your review.",
                [
                    'action' => 'review_volunteer',
                    'application_id' => $application->id,
                    'applicant_name' => $application->user->name
                ]
            );
        }
    }

    public static function newProjectApplicationForAdmin($application)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            self::send(
                $admin->id,
                'new_project_application',
                'New Project Application',
                "A new project application for '{$application->project->title}' requires your review.",
                [
                    'action' => 'review_project_application',
                    'application_id' => $application->id,
                    'project_title' => $application->project->title,
                    'applicant_name' => $application->user->name
                ]
            );
        }
    }
}
