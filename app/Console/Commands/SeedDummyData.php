<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDummyData extends Command
{
    protected $signature = 'fixly:seed-dummy
                            {--users=10 : Number of regular users to create}
                            {--workers=5 : Number of workers to create}
                            {--fresh : Truncate users, workers, requests, ratings before seeding}';

    protected $description = 'Seed dummy users and workers for Fixly (with linked worker profiles)';

    private array $cities = ['Cairo', 'Giza', 'Alexandria', 'Mansoura', 'Tanta', 'Aswan', 'Luxor', 'Suez'];

    private array $areas = [
        ['Nasr City', 'Heliopolis', 'Maadi'],
        ['Dokki', 'Mohandessin', 'Haram'],
        ['Smouha', 'Sidi Gaber', 'Montazah'],
        ['Talkha', 'Mit Ghamr'],
        ['Mahalla', 'Kafr el-Zayat'],
        ['Aswan City', 'Kom Ombo'],
        ['Luxor City', 'Karnak'],
        ['Suez City', 'Ain Sokhna'],
    ];

    private array $workingDays = [
        ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday'],
        ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
        ['saturday', 'monday', 'wednesday', 'friday'],
        ['saturday', 'sunday', 'tuesday', 'thursday'],
    ];

    public function handle(): int
    {
        $userCount   = (int) $this->option('users');
        $workerCount = (int) $this->option('workers');

        if ($this->option('fresh')) {
            $this->truncateTables();
        }

        $this->info('🌱 Seeding dummy data for Fixly...');

        $userIds   = $this->seedUsers($userCount);
        $workerIds = $this->seedWorkers($workerCount);

        $this->info('');
        $this->info("✅ Done! Created:");
        $this->table(
            ['Type', 'Count'],
            [
                ['Regular Users', count($userIds)],
                ['Workers (user + worker profile)', count($workerIds)],
            ]
        );

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Truncate
    // -------------------------------------------------------------------------

    private function truncateTables(): void
    {
        $this->warn('⚠  --fresh: truncating ratings, requests, workers, users...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ratings')->truncate();
        DB::table('requests')->truncate();
        DB::table('workers')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->line('Tables cleared.');
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    private function seedUsers(int $count): array
    {
        $this->line("👤 Creating {$count} regular users...");
        $ids = [];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $cityIndex = array_rand($this->cities);
            $areas     = $this->areas[$cityIndex] ?? $this->areas[0];

            $id = DB::table('users')->insertGetId([
                'name'       => $this->fakeName(),
                'phone'      => $this->fakePhone(),
                'password'   => Hash::make('password'),
                'role'       => 'user',
                'city'       => $this->cities[$cityIndex],
                // 'areas'      => implode(',', (array) fake()->randomElements($areas, rand(1, count($areas)))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ids[] = $id;
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        return $ids;
    }

    // -------------------------------------------------------------------------
    // Workers
    // -------------------------------------------------------------------------

    private function seedWorkers(int $count): array
    {
        $this->line("🔧 Creating {$count} workers (user + worker profile)...");
        $ids = [];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $cityIndex = array_rand($this->cities);
            $areas     = $this->areas[$cityIndex] ?? $this->areas[0];
            $jobTypeId = rand(1, 7);

            // 1. Create the users row with role = 'worker'
            $userId = DB::table('users')->insertGetId([
                'name'       => $this->fakeName(),
                'phone'      => $this->fakePhone(),
                'password'   => Hash::make('password'),
                'role'       => 'worker',
                'city'       => $this->cities[$cityIndex],
                'areas' => implode(',', $this->randomElements($areas, rand(1, count($areas)))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Create the workers profile row
            $workerId = DB::table('workers')->insertGetId([
                'user_id'                => $userId,
                'job_type_id'            => $jobTypeId,
                'is_available'           => (bool) rand(0, 1),
                'is_verified'            => (bool) rand(0, 1),
                'rating'                 => round(rand(30, 50) / 10, 2), // 3.0 – 5.0
                'working_days'           => json_encode($this->workingDays[array_rand($this->workingDays)]),
                'completed_jobs_count'   => rand(0, 30),
                'is_payment_pending'     => false,
                'total_amount_due'       => 0.00,
                'total_amount_paid'      => 0.00,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            $ids[] = ['user_id' => $userId, 'worker_id' => $workerId];
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        return $ids;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function fakeName(): string
    {
        $firstNames = ['Ahmed', 'Mohamed', 'Omar', 'Ali', 'Hassan', 'Khaled', 'Youssef',
                       'Sara', 'Nour', 'Aya', 'Mona', 'Rania', 'Heba', 'Dina', 'Mariam'];
        $lastNames  = ['Hassan', 'Ibrahim', 'Mostafa', 'Mahmoud', 'Sayed', 'Ali',
                       'Khalil', 'Farouk', 'Nasser', 'Zaki', 'Amin', 'Samir'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function fakePhone(): string
    {
        // Egyptian mobile prefixes: 010, 011, 012, 015
        $prefixes = ['010', '011', '012', '015'];
        return $prefixes[array_rand($prefixes)] . rand(10000000, 99999999);
    }
    private function randomElements(array $array, int $count): array
{
    $count = min($count, count($array));
    $keys  = array_rand($array, $count);
    return array_map(fn ($k) => $array[$k], (array) $keys);
}
}