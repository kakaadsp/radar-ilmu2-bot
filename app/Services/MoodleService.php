<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleService
{
    private string $baseUrl;
    private string $service;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.moodle.base_url'), '/');
        $this->service = config('services.moodle.service', 'moodle_mobile_app');
    }

    /**
     * Fetch a Moodle token using student credentials.
     * Password is NEVER stored — it is only used in this one call.
     * Tries the exact input first, then tries NPM/username fallback if token fetch fails.
     *
     * @throws \Exception
     */
    public function fetchToken(string $username, string $password): string
    {
        $username = trim($username);

        // Attempt 1: Try exact input as entered by user
        try {
            return $this->requestToken($username, $password);
        } catch (\Exception $e1) {
            // Attempt 2: If full email was provided, try NPM prefix only (part before @)
            if (str_contains($username, '@')) {
                $npm = explode('@', $username)[0];
                try {
                    return $this->requestToken($npm, $password);
                } catch (\Exception $e2) {
                    throw $e1; // throw original error if fallback also fails
                }
            }

            // Attempt 3: If NPM prefix only was provided, try appending @student.upnjatim.ac.id
            if (!str_contains($username, '@')) {
                try {
                    return $this->requestToken($username . '@student.upnjatim.ac.id', $password);
                } catch (\Exception $e3) {
                    throw $e1;
                }
            }

            throw $e1;
        }
    }

    /**
     * Make raw HTTP call to Moodle token API.
     *
     * @throws \Exception
     */
    private function requestToken(string $username, string $password): string
    {
        try {
            $response = Http::timeout(15)
                ->withoutVerifying() // campus cert may not verify externally
                ->get("{$this->baseUrl}/login/token.php", [
                    'username' => $username,
                    'password' => $password,
                    'service'  => $this->service,
                ]);

            $data = $response->json();

            if (isset($data['error'])) {
                Log::warning('Moodle token fetch error', ['error' => $data['error'], 'username' => $username]);
                throw new \Exception($data['error']);
            }

            if (empty($data['token'])) {
                throw new \Exception('Token tidak ditemukan dalam respons Moodle.');
            }

            return $data['token'];
        } catch (ConnectionException $e) {
            Log::error('Moodle connection failed', ['message' => $e->getMessage()]);
            throw new \Exception('Tidak bisa terhubung ke server Ilmu 2. Coba lagi dalam beberapa menit.');
        }
    }

    /**
     * Fetch upcoming assignment events from Moodle calendar.
     *
     * @throws \Exception
     */
    public function fetchUpcomingTasks(string $token, int $daysAhead = 30): array
    {
        try {
            $timeSort    = now()->timestamp;
            $timeEnd     = now()->addDays($daysAhead)->timestamp;

            $response = Http::timeout(20)
                ->withoutVerifying()
                ->get("{$this->baseUrl}/webservice/rest/server.php", [
                    'wstoken'       => $token,
                    'wsfunction'    => 'core_calendar_get_action_events_by_timesort',
                    'moodlewsrestformat' => 'json',
                    'timesortfrom'  => $timeSort,
                    'timesortto'    => $timeEnd,
                    'limitnum'      => 50,
                ]);

            $data = $response->json();

            if (isset($data['exception'])) {
                $errorCode = $data['errorcode'] ?? '';
                Log::warning('Moodle API exception', ['errorcode' => $errorCode, 'message' => $data['message'] ?? '']);

                // Token expired / invalid
                if (in_array($errorCode, ['invalidtoken', 'accessexception', 'invalidparameter'])) {
                    throw new \Exception('INVALID_TOKEN');
                }

                throw new \Exception($data['message'] ?? 'Moodle API error');
            }

            return $data['events'] ?? [];
        } catch (ConnectionException $e) {
            Log::error('Moodle fetch tasks connection failed', ['message' => $e->getMessage()]);
            throw new \Exception('CONNECTION_ERROR');
        }
    }

    /**
     * Parse raw Moodle event into a clean task array.
     */
    public function parseTask(array $event): array
    {
        $taskId = (string) ($event['id'] ?? '');
        $moodleUrl = "https://ilmu2.upnjatim.ac.id/mod/assign/view.php?id=" . ($event['instance'] ?? $taskId);

        return [
            'moodle_task_id'  => $taskId,
            'task_name'       => $event['name'] ?? 'Tugas',
            'course_name'     => $event['course']['shortname'] ?? '',
            'course_fullname' => $event['course']['fullname'] ?? '',
            'deadline'        => \Carbon\Carbon::createFromTimestamp($event['timesort'] ?? now()->timestamp),
            'moodle_url'      => $moodleUrl,
        ];
    }
}
