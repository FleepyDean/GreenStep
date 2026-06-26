<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;

/**
 * GoalController
 *
 * Handles personal carbon-reduction goal setting and pace projection
 */
class GoalController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * GET /api/goal
     * Returns the user's current goal and 30-day projection
     */
    public function getGoal(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        try {
            $goal = $this->ensureGoal($userId);
            $projection = $this->calculateProjection($userId, $goal);

            $response->getBody()->write(json_encode([
                'success' => true,
                'goal' => $goal,
                'projection' => $projection
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            error_log('GoalController::getGoal error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to load goal projection'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * PUT /api/goal
     * Update target reduction, duration, baseline, or reset the start date
     */
    public function updateGoal(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = (int) ($user['id'] ?? 0);

        $data = $request->getParsedBody() ?? [];

        $targetReduction = isset($data['target_reduction_percent']) ? (float) $data['target_reduction_percent'] : null;
        $durationDays = isset($data['goal_duration_days']) ? (int) $data['goal_duration_days'] : null;
        $baseline = isset($data['baseline_footprint']) ? (float) $data['baseline_footprint'] : null;
        $resetStart = !empty($data['reset_start_date']);

        if ($targetReduction !== null && ($targetReduction <= 0 || $targetReduction > 100)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Target reduction must be between 0 and 100'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($durationDays !== null && $durationDays < 1) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Goal duration must be at least 1 day'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($baseline !== null && $baseline < 0) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Baseline footprint cannot be negative'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $goal = $this->ensureGoal($userId);

            $updates = [];
            $params = [':user_id' => $userId];

            if ($targetReduction !== null) {
                $updates[] = 'target_reduction_percent = :target_reduction_percent';
                $params[':target_reduction_percent'] = $targetReduction;
            }

            if ($durationDays !== null) {
                $updates[] = 'goal_duration_days = :goal_duration_days';
                $params[':goal_duration_days'] = $durationDays;
            }

            if ($baseline !== null) {
                $updates[] = 'baseline_footprint = :baseline_footprint';
                $params[':baseline_footprint'] = $baseline;
            }

            if ($resetStart) {
                $updates[] = 'goal_start_date = CURDATE()';
            }

            if (!empty($updates)) {
                $sql = 'UPDATE User SET ' . implode(', ', $updates) . ' WHERE id = :user_id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            $goal = $this->ensureGoal($userId);
            $projection = $this->calculateProjection($userId, $goal);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Goal updated successfully',
                'goal' => $goal,
                'projection' => $projection
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            error_log('GoalController::updateGoal error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update goal'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Ensure the user has a goal record with defaults and baseline calculated
     */
    private function ensureGoal(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT target_reduction_percent, goal_duration_days, goal_start_date, baseline_footprint
            FROM User
            WHERE id = :user_id
            LIMIT 1
        ');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new PDOException('User not found');
        }

        $targetReduction = (float) ($row['target_reduction_percent'] ?? 20.00);
        $durationDays = (int) ($row['goal_duration_days'] ?? 30);
        $startDate = $row['goal_start_date'];
        $baseline = isset($row['baseline_footprint']) ? (float) $row['baseline_footprint'] : null;

        if (empty($startDate)) {
            $startDate = date('Y-m-d');
            $this->db->prepare('UPDATE User SET goal_start_date = :start_date WHERE id = :user_id')
                ->execute([':start_date' => $startDate, ':user_id' => $userId]);
        }

        if ($baseline === null || $baseline <= 0) {
            $baseline = $this->calculateBaseline($userId, $startDate);
            $this->db->prepare('UPDATE User SET baseline_footprint = :baseline WHERE id = :user_id')
                ->execute([':baseline' => $baseline, ':user_id' => $userId]);
        }

        return [
            'target_reduction_percent' => $targetReduction,
            'goal_duration_days' => $durationDays,
            'goal_start_date' => $startDate,
            'baseline_footprint' => $baseline
        ];
    }

    /**
     * Calculate a daily baseline footprint from historical activity before the goal
     * Falls back to a default if no history exists
     */
    private function calculateBaseline(int $userId, string $startDate): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(AVG(daily_total), 0) as avg_daily
            FROM (
                SELECT logged_on, SUM(carbon_footprint) as daily_total
                FROM ActivityLog
                WHERE user_id = :user_id AND logged_on < :start_date
                GROUP BY logged_on
            ) as daily_history
        ');
        $stmt->execute([':user_id' => $userId, ':start_date' => $startDate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $avgDaily = (float) ($row['avg_daily'] ?? 0);

        if ($avgDaily <= 0) {
            return 15.0000;
        }

        return round($avgDaily, 4);
    }

    /**
     * Calculate the projection from current pace to the end of the goal window
     */
    private function calculateProjection(int $userId, array $goal): array
    {
        $today = date('Y-m-d');
        $startDate = $goal['goal_start_date'];
        $durationDays = (int) $goal['goal_duration_days'];
        $baseline = (float) $goal['baseline_footprint'];
        $targetPercent = (float) $goal['target_reduction_percent'];

        error_log("Goal calculation debug: today=$today, startDate=$startDate, durationDays=$durationDays");

        $startTimestamp = strtotime($startDate);
        $todayTimestamp = strtotime($today);
        $daysElapsed = max(1, (int) round(($todayTimestamp - $startTimestamp) / 86400) + 1);
        $daysElapsed = min($daysElapsed, $durationDays);
        $daysRemaining = max(0, $durationDays - $daysElapsed);
        $endDate = date('Y-m-d', strtotime($startDate . ' + ' . ($durationDays - 1) . ' days'));

        error_log("Goal calculation debug: daysElapsed=$daysElapsed, daysRemaining=$daysRemaining");

        $stmt = $this->db->prepare('
            SELECT
                COALESCE(SUM(carbon_footprint), 0) as total_logged,
                COUNT(DISTINCT logged_on) as days_with_logs
            FROM ActivityLog
            WHERE user_id = :user_id AND logged_on >= :start_date AND logged_on <= :today
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':today' => $today
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Goal calculation SQL debug: user_id=$userId, start_date=$startDate, today=$today");
        error_log("Goal calculation SQL result: " . json_encode($row));

        $totalLogged = (float) ($row['total_logged'] ?? 0);
        $daysWithLogs = (int) ($row['days_with_logs'] ?? 0);
        $daysWithoutLogs = max(0, $daysElapsed - $daysWithLogs);

        error_log("Goal calculation debug: totalLogged=$totalLogged, daysWithLogs=$daysWithLogs, daysWithoutLogs=$daysWithoutLogs");

        // Days without any tracked activity are assumed to be at the baseline pace
        $actualFootprint = $totalLogged + ($baseline * $daysWithoutLogs);
        $expectedFootprint = $baseline * $daysElapsed;
        $savingsSoFar = $expectedFootprint - $actualFootprint;
        $pace = $savingsSoFar / $daysElapsed;
        $projectedTotalSavings = $pace * $durationDays;

        $totalBaselineFootprint = $baseline * $durationDays;
        $targetTotalSavings = $totalBaselineFootprint * ($targetPercent / 100);
        $projectedReductionPercent = $totalBaselineFootprint > 0
            ? ($projectedTotalSavings / $totalBaselineFootprint) * 100
            : 0;

        $onTrack = $projectedTotalSavings >= $targetTotalSavings;
        $progressPercent = $targetTotalSavings > 0
            ? min(100, ($savingsSoFar / $targetTotalSavings) * 100)
            : 0;

        $neededDailyPace = $daysRemaining > 0
            ? max(0, ($targetTotalSavings - $savingsSoFar) / $daysRemaining)
            : 0;

        return [
            'today' => $today,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'duration_days' => $durationDays,
            'baseline_footprint' => round($baseline, 2),
            'actual_footprint_so_far' => round($actualFootprint, 2),
            'expected_footprint_so_far' => round($expectedFootprint, 2),
            'savings_so_far' => round($savingsSoFar, 2),
            'days_with_logs' => $daysWithLogs,
            'days_without_logs' => $daysWithoutLogs,
            'average_pace_per_day' => round($pace, 2),
            'projected_total_savings' => round($projectedTotalSavings, 2),
            'target_total_savings' => round($targetTotalSavings, 2),
            'projected_reduction_percent' => round($projectedReductionPercent, 2),
            'target_reduction_percent' => $targetPercent,
            'progress_percent' => round(max(0, min(100, $progressPercent)), 2),
            'on_track' => $onTrack,
            'needed_daily_pace' => round($neededDailyPace, 2)
        ];
    }
}
