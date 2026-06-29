<?php

namespace Database\Seeders;

use App\Enums\AuthEvent;
use App\Enums\BackupStatus;
use App\Enums\IpListType;
use App\Enums\NotificationType;
use App\Enums\ProjectStatus;
use App\Enums\RecordStatus;
use App\Enums\UserExportStatus;
use App\Enums\UserImportStatus;
use App\Enums\UserStatus;
use App\Models\Asset;
use App\Models\Backup;
use App\Models\DataTag;
use App\Models\File;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Ip;
use App\Models\LoginHistory;
use App\Models\Milestone;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Person;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\Theme;
use App\Models\User;
use App\Models\UserExport;
use App\Models\UserImport;
use App\Notifications\AdminNotification;
use Illuminate\Console\OutputStyle as Output;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Standalone, high-volume DEMO seeder for local UI / filter / performance
 * testing. It is intentionally NOT registered in DatabaseSeeder — run it on
 * demand via `make seed-demo` (override volume with `DEMO_SCALE`, e.g.
 * `make seed-demo DEMO_SCALE=200`).
 *
 * It generates large, combinatorial data so every list, filter, badge colour
 * and pagination path is exercised: each enum/status value appears, a slice of
 * rows are inactive (record_status), some carry very long names/descriptions to
 * surface UI truncation/overflow, created_at is spread across a year for date
 * filters, and the pivots (tags, project assets, team members) are populated.
 *
 * Notes:
 * - Unique columns are built deterministically from an index — never via
 *   fake()->unique(), which overflows past a few thousand rows.
 * - Model events stay enabled so the HasToken `creating` hook fills tokens;
 *   auditing is already off for console commands (config/audit.php).
 * - Users + notifications + pivots are bulk-inserted (one shared bcrypt hash,
 *   no per-row events) for speed; everything else uses Eloquent for correctness.
 */
class DemoSeeder extends Seeder
{
    private const CHUNK = 500;

    /** Rows are spread across this many organizations (so per-org filters are dense). */
    private const RICH_ORGS = 50;

    /** @var array<int, string> */
    private array $sentences = [];

    /** @var array<int, string> */
    private array $words = [];

    private string $longText = '';

    private string $longBlob = '';

    private string $longName = '';

    /** Per-run token mixed into unique columns so the seeder is safe to re-run. */
    private string $runId = '';

    public function run(): void
    {
        $scale = max(1, (int) (getenv('DEMO_SCALE') ?: 1000));
        $this->runId = Str::lower(Str::random(5));
        $out = $this->command->getOutput();
        $out->writeln("<info>DemoSeeder: generating combinatorial demo data (scale={$scale}). Local UI/perf testing only.</info>");

        $this->bootPools();

        Model::unguard();

        try {
            $userIds = $this->seedUsers($scale, $out);
            // A row displayed via a belongsTo must point at an ACTIVE parent: the
            // `active` global scope hides inactive orgs/users, so a relation to an
            // inactive row resolves to null and null-crashes the row serializer.
            // Inactive users still exist (for the users list's inactive filter) —
            // they're just never used as a displayed FK.
            $activeUserIds = User::query()->whereIn('id', $userIds)->pluck('id')->all();
            $this->seedThemes(min($scale, 60), $out);
            $this->seedIps($scale, $out);
            $fileIds = $this->seedFiles($scale, $userIds, $out);
            $this->seedBackups(min($scale, 400), $out);
            $this->seedImportsAndExports(min($scale, 400), $userIds, $out);
            $this->seedLoginHistory($scale, $userIds, $out);
            $this->seedNotifications($scale, $userIds, $out);

            $orgIds = $this->seedOrganizations($scale, $activeUserIds, $out);
            // Parent pool for org-scoped children — ACTIVE demo orgs only (the
            // active scope would otherwise null the organization relation).
            $richOrgs = Organization::query()
                ->whereIn('id', $orgIds)
                ->orderBy('id')
                ->limit(self::RICH_ORGS)
                ->pluck('id')
                ->all();

            $tagsByOrg = $this->seedDataTags($scale, $richOrgs, $out);
            $catsByOrg = $this->seedTeamCategories($scale, $richOrgs, $out);
            $rolesByOrg = $this->seedOrganizationRoles($scale, $richOrgs, $out);

            $projectsByOrg = $this->seedProjects($scale, $richOrgs, $out);
            $assetsByOrg = $this->seedAssets($scale, $richOrgs, $out);
            $forms = $this->seedForms($scale, $richOrgs, $out);
            $refsByOrg = $this->seedReferenceFiles($scale, $richOrgs, $fileIds, $out);

            $this->seedTaggables($tagsByOrg, [
                (new Project)->getMorphClass() => $projectsByOrg,
                (new Asset)->getMorphClass() => $assetsByOrg,
                (new ReferenceFile)->getMorphClass() => $refsByOrg,
                (new Form)->getMorphClass() => $this->groupBy($forms, 'org', 'id'),
            ], $out);

            $bindings = $this->seedProjectAssets($projectsByOrg, $assetsByOrg, $out);
            $this->seedBoards($scale, $bindings, $tagsByOrg, $refsByOrg, $activeUserIds, $out);

            $teams = $this->seedTeams($scale, $richOrgs, $catsByOrg, $rolesByOrg, $out);
            $this->seedPeople($scale, $teams, $activeUserIds, $out);
            $this->seedFormResponses($scale, $forms, $activeUserIds, $out);
        } finally {
            Model::reguard();
        }

        $out->writeln('<info>DemoSeeder: done.</info>');
    }

    // ── Global / system entities ────────────────────────────────────────────

    /** @return array<int, int> all user ids (incl. previously-seeded accounts) */
    private function seedUsers(int $count, Output $out): array
    {
        $password = Hash::make('password');
        $statuses = UserStatus::cases();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $name = $i % 40 === 0
                ? 'Demo '.$this->longName
                : 'Demo User '.$this->pad($i).' '.$this->word($i);
            $at = $this->madeAt($i)->format('Y-m-d H:i:s');

            $rows[] = [
                'token' => (string) Str::uuid(),
                'name' => $name,
                'email' => "demo-{$this->runId}-{$i}@demo.test",
                'username' => $i % 3 === 0 ? null : "u_{$this->runId}_{$i}",
                'email_verified_at' => $i % 9 === 0 ? null : $at,
                'password' => $password,
                'user_status' => $statuses[$i % count($statuses)]->value,
                'record_status' => $this->rstatus($i),
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $out->writeln("  users           +{$count}");

        return DB::table('users')->pluck('id')->all();
    }

    private function seedThemes(int $count, Output $out): void
    {
        DB::transaction(function () use ($count): void {
            for ($i = 1; $i <= $count; $i++) {
                $hue = ($i * 37) % 360;
                Theme::create([
                    'name' => $this->label('Theme', $i),
                    'description' => $this->desc($i),
                    'preview_image' => null,
                    'is_default' => false,
                    'tokens' => [
                        'light' => ['--primary' => "{$hue} 47% 11%", '--background' => '0 0% 100%'],
                        'dark' => ['--primary' => "{$hue} 40% 98%", '--background' => '222 84% 5%'],
                    ],
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  themes          +{$count}");
    }

    private function seedIps(int $count, Output $out): void
    {
        $types = IpListType::cases();
        $base = random_int(0, 255);

        DB::transaction(function () use ($count, $types, $base): void {
            for ($i = 1; $i <= $count; $i++) {
                Ip::create([
                    'ip_address' => sprintf('10.%d.%d.%d', $base, ($i >> 8) & 255, $i & 255),
                    'list_type' => $types[$i % count($types)],
                    'description' => $this->desc($i),
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  ips             +{$count}");
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedFiles(int $count, array $userIds, Output $out): array
    {
        $kinds = [
            ['png', 'image/png'], ['jpg', 'image/jpeg'], ['pdf', 'application/pdf'],
            ['csv', 'text/csv'], ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
        $tags = [null, 'avatar', 'document', 'banner'];
        $ids = [];

        DB::transaction(function () use ($count, $userIds, $kinds, $tags, &$ids): void {
            for ($i = 1; $i <= $count; $i++) {
                [$ext, $mime] = $kinds[$i % count($kinds)];
                $base = $i % 40 === 0 ? Str::slug($this->longName) : 'demo-file-'.$this->pad($i).'-'.Str::slug($this->word($i));

                $file = File::create([
                    'original_name' => "{$base}.{$ext}",
                    'extension' => $ext,
                    'mime' => $mime,
                    'size' => $i % 13 === 0 ? random_int(50_000_000, 250_000_000) : random_int(1024, 5_000_000),
                    'disk' => 'uploads',
                    'path' => "uploads/{$base}.{$ext}",
                    'owner_id' => $i % 5 === 0 ? null : $userIds[$i % count($userIds)],
                    'tag' => $tags[$i % count($tags)],
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $ids[] = $file->id;
            }
        });

        $out->writeln("  files           +{$count}");

        return $ids;
    }

    private function seedBackups(int $count, Output $out): void
    {
        $statuses = BackupStatus::cases();

        DB::transaction(function () use ($count, $statuses): void {
            for ($i = 1; $i <= $count; $i++) {
                $status = $statuses[$i % count($statuses)];
                Backup::create([
                    'filename' => 'backups/'.$this->madeAt($i)->format('Y-m-d-His')."-{$i}.zip",
                    'disk' => 'backups',
                    'size' => random_int(100_000, 50_000_000),
                    'status' => $status,
                    'error_message' => str_contains($status->name, 'Failed') ? $this->longText : null,
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  backups         +{$count}");
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedImportsAndExports(int $count, array $userIds, Output $out): void
    {
        $importStatuses = UserImportStatus::cases();
        $exportStatuses = UserExportStatus::cases();
        $formats = ['csv', 'xlsx', 'pdf'];

        DB::transaction(function () use ($count, $userIds, $importStatuses, $exportStatuses, $formats): void {
            for ($i = 1; $i <= $count; $i++) {
                $total = random_int(0, 5000);
                $failed = random_int(0, (int) ($total / 10));

                UserImport::create([
                    'user_id' => $userIds[$i % count($userIds)],
                    'token' => Str::random(48),
                    'resource' => 'users',
                    'filename' => 'imports/users-'.Str::lower(Str::random(8)).'.csv',
                    'total' => $total,
                    'success' => $total - $failed,
                    'failed' => $failed,
                    'error_report_path' => $failed > 0 ? 'imports/errors-'.Str::lower(Str::random(8)).'.csv' : null,
                    'status' => $importStatuses[$i % count($importStatuses)],
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);

                $exportStatus = $exportStatuses[$i % count($exportStatuses)];
                UserExport::create([
                    'user_id' => $userIds[$i % count($userIds)],
                    'token' => Str::random(48),
                    'format' => $formats[$i % count($formats)],
                    'resource' => 'users',
                    'filters' => ['search' => '', 'inactive' => (bool) ($i % 2)],
                    'row_count' => random_int(0, 5000),
                    'total_rows' => random_int(0, 5000),
                    'processed_rows' => random_int(0, 5000),
                    'filename' => 'exports/users-'.Str::lower(Str::random(8)).'.csv',
                    'status' => $exportStatus,
                    'error_message' => str_contains($exportStatus->name, 'Failed') ? $this->longText : null,
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  imports/exports +{$count} each");
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedLoginHistory(int $count, array $userIds, Output $out): void
    {
        $events = AuthEvent::cases();
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
        ];

        DB::transaction(function () use ($count, $userIds, $events, $agents): void {
            for ($i = 1; $i <= $count; $i++) {
                LoginHistory::create([
                    'user_id' => $i % 11 === 0 ? null : $userIds[$i % count($userIds)],
                    'event' => $events[$i % count($events)],
                    'ip_address' => sprintf('172.%d.%d.%d', ($i >> 16) & 255, ($i >> 8) & 255, $i & 255),
                    'user_agent' => $agents[$i % count($agents)],
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  login history   +{$count}");
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedNotifications(int $count, array $userIds, Output $out): void
    {
        $types = NotificationType::cases();
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $type = $types[$i % count($types)];
            $message = $i % 20 === 0
                ? $this->longBlob
                : "Notification {$i}: ".$this->sentences[$i % count($this->sentences)];
            $at = $this->madeAt($i)->format('Y-m-d H:i:s');

            $rows[] = [
                'id' => (string) Str::uuid(),
                'type' => AdminNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $userIds[$i % count($userIds)],
                'data' => json_encode(['type' => $type->value, 'message' => $message, 'link' => null]),
                'read_at' => $i % 3 === 0 ? $at : null,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }

        $out->writeln("  notifications   +{$count}");
    }

    // ── Organizations + org-scoped entities ──────────────────────────────────

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function seedOrganizations(int $count, array $userIds, Output $out): array
    {
        $ids = [];

        DB::transaction(function () use ($count, $userIds, &$ids): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = Organization::create([
                    'name' => $this->label('Organization', $i),
                    'description' => $this->desc($i),
                    'point_of_contact_id' => $i % 4 === 0 ? null : $userIds[$i % count($userIds)],
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $ids[] = $org->id;
            }
        });

        $out->writeln("  organizations   +{$count}");

        return $ids;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array<int, int>> orgId => [tagId, …]
     */
    private function seedDataTags(int $count, array $orgs, Output $out): array
    {
        $colors = DataTag::COLORS;
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, $colors, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $tag = DataTag::create([
                    'name' => $this->label('Tag', $i),
                    'description' => $this->desc($i),
                    'color' => $colors[$i % count($colors)],
                    'organization_id' => $org,
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $byOrg[$org][] = $tag->id;
            }
        });

        $out->writeln("  data tags       +{$count}");

        return $byOrg;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array<int, int>>
     */
    private function seedTeamCategories(int $count, array $orgs, Output $out): array
    {
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $rs = $this->rstatus($i);
                $cat = TeamCategory::create([
                    'name' => $this->label('Category', $i),
                    'description' => $this->desc($i),
                    'organization_id' => $org,
                    'record_status' => $rs,
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                // Only active categories are usable as a (displayed) team parent.
                if ($rs === RecordStatus::Active->value) {
                    $byOrg[$org][] = $cat->id;
                }
            }
        });

        $out->writeln("  team categories +{$count}");

        return $byOrg;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array<int, int>>
     */
    private function seedOrganizationRoles(int $count, array $orgs, Output $out): array
    {
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $rs = $this->rstatus($i);
                $role = OrganizationRole::create([
                    'name' => $this->label('Role', $i),
                    'description' => $this->desc($i),
                    'organization_id' => $org,
                    'record_status' => $rs,
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                // Only active roles are usable as a (displayed) team/person parent.
                if ($rs === RecordStatus::Active->value) {
                    $byOrg[$org][] = $role->id;
                }
            }
        });

        $out->writeln("  org roles       +{$count}");

        return $byOrg;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array<int, int>>
     */
    private function seedProjects(int $count, array $orgs, Output $out): array
    {
        $statuses = ProjectStatus::cases();
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, $statuses, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $project = Project::create([
                    'name' => $this->label('Project', $i),
                    'description' => $this->desc($i),
                    'private' => $i % 2 === 0,
                    'status' => $statuses[$i % count($statuses)],
                    'organization_id' => $org,
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $byOrg[$org][] = $project->id;
            }
        });

        $out->writeln("  projects        +{$count}");

        return $byOrg;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array<int, int>>
     */
    private function seedAssets(int $count, array $orgs, Output $out): array
    {
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $address = match (true) {
                    $i % 10 === 0 => $this->longText,
                    $i % 7 === 0 => '',
                    default => $i.' '.$this->word($i).' Street, '.$this->word($i * 2).' City',
                };
                $asset = Asset::create([
                    'name' => $this->label('Asset', $i),
                    'id_code' => 'AST-'.$this->runId.'-'.$this->pad($i),
                    'address' => $address,
                    'organization_id' => $org,
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $byOrg[$org][] = $asset->id;
            }
        });

        $out->writeln("  assets          +{$count}");

        return $byOrg;
    }

    /**
     * @param  array<int, int>  $orgs
     * @return array<int, array{id: int, org: int, fields: array<int, mixed>}>
     */
    private function seedForms(int $count, array $orgs, Output $out): array
    {
        $forms = [];

        DB::transaction(function () use ($count, $orgs, &$forms): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $fields = $i % 9 === 0 ? [] : $this->sampleFields();
                $form = Form::create([
                    'title' => $this->label('Form', $i),
                    'description' => $this->desc($i),
                    'form_fields' => $fields,
                    'organization_id' => $org,
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $forms[] = ['id' => $form->id, 'org' => $org, 'fields' => $fields];
            }
        });

        $out->writeln("  forms           +{$count}");

        return $forms;
    }

    /**
     * @param  array<int, int>  $orgs
     * @param  array<int, int>  $fileIds
     * @return array<int, array<int, int>>
     */
    private function seedReferenceFiles(int $count, array $orgs, array $fileIds, Output $out): array
    {
        $byOrg = [];

        DB::transaction(function () use ($count, $orgs, $fileIds, &$byOrg): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $ref = ReferenceFile::create([
                    'name' => $this->label('Reference', $i),
                    'description' => $this->desc($i),
                    'organization_id' => $org,
                    'file_id' => ($i % 3 === 0 || $fileIds === []) ? null : $fileIds[$i % count($fileIds)],
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                $byOrg[$org][] = $ref->id;
            }
        });

        $out->writeln("  reference files +{$count}");

        return $byOrg;
    }

    // ── Pivots ────────────────────────────────────────────────────────────────

    /**
     * @param  array<int, array<int, int>>  $tagsByOrg
     * @param  array<string, array<int, array<int, int>>>  $taggables  morphType => (orgId => [id, …])
     */
    private function seedTaggables(array $tagsByOrg, array $taggables, Output $out): void
    {
        $rows = [];
        $total = 0;
        $c = 0;

        foreach ($taggables as $morphType => $byOrg) {
            foreach ($byOrg as $org => $ids) {
                $tags = $tagsByOrg[$org] ?? [];
                if ($tags === []) {
                    continue;
                }
                foreach ($ids as $id) {
                    foreach ($this->pickSome($tags, $c++ % 6) as $tagId) {
                        $rows[] = ['data_tag_id' => $tagId, 'taggable_id' => $id, 'taggable_type' => $morphType];
                        $total++;
                    }
                    if (count($rows) >= self::CHUNK) {
                        DB::table('taggables')->insert($rows);
                        $rows = [];
                    }
                }
            }
        }

        if ($rows !== []) {
            DB::table('taggables')->insert($rows);
        }

        $out->writeln("  taggables       +{$total}");
    }

    /**
     * @param  array<int, array<int, int>>  $projectsByOrg
     * @param  array<int, array<int, int>>  $assetsByOrg
     * @return array<int, array{project: int, asset: int, org: int}> the bindings created (board parents)
     */
    private function seedProjectAssets(array $projectsByOrg, array $assetsByOrg, Output $out): array
    {
        $statuses = ProjectStatus::cases();
        $rows = [];
        $bindings = [];
        $total = 0;
        $cursor = 0;
        $pc = 0;

        foreach ($projectsByOrg as $org => $projectIds) {
            $assets = $assetsByOrg[$org] ?? [];
            if ($assets === []) {
                continue;
            }
            foreach ($projectIds as $projectId) {
                foreach ($this->pickSome($assets, 1 + ($pc++ % 8)) as $assetId) {
                    $rows[] = [
                        'project_id' => $projectId,
                        'asset_id' => $assetId,
                        'status' => $statuses[$cursor++ % count($statuses)]->value,
                    ];
                    $bindings[] = ['project' => $projectId, 'asset' => $assetId, 'org' => $org];
                    $total++;
                }
                if (count($rows) >= self::CHUNK) {
                    DB::table('project_assets')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('project_assets')->insert($rows);
        }

        $out->writeln("  project assets  +{$total}");

        return $bindings;
    }

    /**
     * Milestone (column) + task (card) boards for a bounded slice of project-asset
     * bindings. Bindings live in active orgs (richOrgs is active-scoped), so the
     * board's org-scoped FKs — assignees (active users), tags and reference files —
     * stay loadable. Tasks exercise every field: assignment trio, private flag,
     * due dates, an optional reference file and attached tags.
     *
     * @param  array<int, array{project: int, asset: int, org: int}>  $bindings
     * @param  array<int, array<int, int>>  $tagsByOrg
     * @param  array<int, array<int, int>>  $refsByOrg
     * @param  array<int, int>  $activeUserIds
     */
    private function seedBoards(int $scale, array $bindings, array $tagsByOrg, array $refsByOrg, array $activeUserIds, Output $out): void
    {
        if ($bindings === [] || $activeUserIds === []) {
            return;
        }

        $boardCount = min(count($bindings), max(20, intdiv($scale, 5)));
        $milestoneTotal = 0;
        $taskTotal = 0;
        $m = 0;
        $t = 0;

        DB::transaction(function () use ($bindings, $tagsByOrg, $refsByOrg, $activeUserIds, $boardCount, &$milestoneTotal, &$taskTotal, &$m, &$t): void {
            for ($b = 0; $b < $boardCount; $b++) {
                $binding = $bindings[$b];
                $org = $binding['org'];
                $tags = $tagsByOrg[$org] ?? [];
                $refs = $refsByOrg[$org] ?? [];

                $columns = 2 + ($b % 4); // 2–5 columns per board
                for ($p = 0; $p < $columns; $p++) {
                    $m++;
                    $milestone = Milestone::create([
                        // First column mirrors the app's default "Misc" milestone.
                        'name' => $p === 0 ? Milestone::DEFAULT_NAME : $this->label('Milestone', $m),
                        'description' => $this->desc($m),
                        'project_id' => $binding['project'],
                        'asset_id' => $binding['asset'],
                        'organization_id' => $org,
                        'position' => $p,
                        'record_status' => RecordStatus::Active->value,
                        'created_at' => $this->madeAt($m),
                        'updated_at' => $this->madeAt($m),
                    ]);
                    $milestoneTotal++;

                    $cards = $p % 6; // 0–5 cards per column
                    for ($k = 0; $k < $cards; $k++) {
                        $t++;
                        $task = Task::create([
                            'name' => $this->label('Task', $t),
                            'description' => $this->desc($t),
                            'milestone_id' => $milestone->id,
                            'organization_id' => $org,
                            'assigned_to_id' => $t % 3 === 0 ? null : $activeUserIds[$t % count($activeUserIds)],
                            'approver_id' => $t % 4 === 0 ? $activeUserIds[($t + 1) % count($activeUserIds)] : null,
                            'observer_id' => $t % 5 === 0 ? $activeUserIds[($t + 2) % count($activeUserIds)] : null,
                            'private' => $t % 2 === 0,
                            'due_date' => $t % 3 === 0 ? null : now()->addDays($t % 30)->toDateString(),
                            'reference_file_id' => ($refs === [] || $t % 4 === 0) ? null : $refs[$t % count($refs)],
                            'position' => $k,
                            'record_status' => $this->rstatus($t),
                            'created_at' => $this->madeAt($t),
                            'updated_at' => $this->madeAt($t),
                        ]);
                        $taskTotal++;

                        if ($tags !== []) {
                            $task->tags()->attach($this->pickSome($tags, $k % 3));
                        }
                    }
                }
            }
        });

        $out->writeln("  milestones      +{$milestoneTotal}");
        $out->writeln("  tasks           +{$taskTotal}");
    }

    // ── Teams / people / responses ─────────────────────────────────────────────

    /**
     * @param  array<int, int>  $orgs
     * @param  array<int, array<int, int>>  $catsByOrg
     * @param  array<int, array<int, int>>  $rolesByOrg
     * @return array<int, array{id: int, org: int, role: int}>
     */
    private function seedTeams(int $count, array $orgs, array $catsByOrg, array $rolesByOrg, Output $out): array
    {
        $teams = [];

        DB::transaction(function () use ($count, $orgs, $catsByOrg, $rolesByOrg, &$teams): void {
            for ($i = 1; $i <= $count; $i++) {
                $org = $orgs[$i % count($orgs)];
                $cats = $catsByOrg[$org] ?? [];
                $roles = $rolesByOrg[$org] ?? [];
                if ($cats === [] || $roles === []) {
                    continue;
                }
                $role = $roles[$i % count($roles)];
                $rs = $this->rstatus($i);
                $team = Team::create([
                    'name' => $this->label('Team', $i),
                    'description' => $this->desc($i),
                    'team_category_id' => $cats[$i % count($cats)],
                    'organization_role_id' => $role,
                    'organization_id' => $org,
                    'record_status' => $rs,
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
                // Only active teams get members — a person's belongsTo team must
                // be loadable (the active scope hides inactive teams).
                if ($rs === RecordStatus::Active->value) {
                    $teams[] = ['id' => $team->id, 'org' => $org, 'role' => $role];
                }
            }
        });

        $out->writeln('  teams           +'.count($teams));

        return $teams;
    }

    /**
     * @param  array<int, array{id: int, org: int, role: int}>  $teams
     * @param  array<int, int>  $userIds
     */
    private function seedPeople(int $count, array $teams, array $userIds, Output $out): void
    {
        if ($teams === [] || $userIds === []) {
            return;
        }

        $perTeam = max(1, intdiv($count, count($teams)));
        $made = 0;
        $u = 0;

        DB::transaction(function () use ($teams, $userIds, $perTeam, $count, &$made, &$u): void {
            foreach ($teams as $team) {
                for ($k = 0; $k < $perTeam && $made < $count; $k++) {
                    // Consecutive users within a team stay distinct (unique team_id+user_id).
                    Person::create([
                        'team_id' => $team['id'],
                        'user_id' => $userIds[$u++ % count($userIds)],
                        'organization_role_id' => $team['role'],
                        'organization_id' => $team['org'],
                        'record_status' => RecordStatus::Active->value,
                        'created_at' => $this->madeAt($made + 1),
                        'updated_at' => $this->madeAt($made + 1),
                    ]);
                    $made++;
                }
            }
        });

        $out->writeln("  people          +{$made}");
    }

    /**
     * @param  array<int, array{id: int, org: int, fields: array<int, mixed>}>  $forms
     * @param  array<int, int>  $userIds
     */
    private function seedFormResponses(int $count, array $forms, array $userIds, Output $out): void
    {
        if ($forms === []) {
            return;
        }

        DB::transaction(function () use ($count, $forms, $userIds): void {
            for ($i = 1; $i <= $count; $i++) {
                $form = $forms[$i % count($forms)];
                FormResponse::create([
                    'form_id' => $form['id'],
                    'answers' => $this->answersFor($form['fields'], $i % 20 === 0),
                    'created_by' => $i % 5 === 0 ? null : $userIds[$i % count($userIds)],
                    'record_status' => $this->rstatus($i),
                    'created_at' => $this->madeAt($i),
                    'updated_at' => $this->madeAt($i),
                ]);
            }
        });

        $out->writeln("  form responses  +{$count}");
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function bootPools(): void
    {
        $this->sentences = array_map(fn () => fake()->sentence(random_int(6, 16)), range(1, 60));
        $this->words = array_map(fn () => ucfirst(fake()->word()), range(1, 80));
        // Capped to fit varchar(255) string columns (name/address/description)
        // while still long enough to surface truncation/overflow in the UI.
        $this->longText = Str::limit(str_repeat(fake()->sentence(12).' ', 4), 230, '');
        // Only used in TEXT/JSON columns (notification data, form answers).
        $this->longBlob = trim(str_repeat(fake()->sentence(20).' ', 8));
        $this->longName = 'Extremely '.implode(' ', array_fill(0, 14, 'Long')).' Name For UI Overflow Testing';
    }

    private function pad(int $i): string
    {
        return str_pad((string) $i, 6, '0', STR_PAD_LEFT);
    }

    private function word(int $i): string
    {
        return $this->words[$i % count($this->words)];
    }

    /** Build a unique, mostly-short label; every 40th gets a very long variant. */
    private function label(string $prefix, int $i): string
    {
        $base = $prefix.' '.$this->pad($i).' '.$this->runId.' '.$this->word($i).' '.$this->word($i * 3 + 1);

        return $i % 40 === 0 ? $base.' '.$this->longName : $base;
    }

    /** Null for some rows, a long blob for some, otherwise a sentence. */
    private function desc(int $i): ?string
    {
        return match (true) {
            $i % 8 === 0 => null,
            $i % 25 === 0 => $this->longText,
            default => $this->sentences[$i % count($this->sentences)],
        };
    }

    /** ~1 in 6 rows inactive, so the "show inactive" filter has data. */
    private function rstatus(int $i): int
    {
        return $i % 6 === 0 ? RecordStatus::Inactive->value : RecordStatus::Active->value;
    }

    /** Spread created_at across the last year for date-range filters. */
    private function madeAt(int $i): Carbon
    {
        return now()->subDays($i % 365)->subSeconds(random_int(0, 86_399));
    }

    /**
     * @param  array<int, int>  $items
     * @return array<int, int>
     */
    private function pickSome(array $items, int $count): array
    {
        if ($count <= 0 || $items === []) {
            return [];
        }
        $count = min($count, count($items));
        $keys = (array) array_rand($items, $count);

        return array_map(fn ($key) => $items[$key], $keys);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int|string, array<int, mixed>>
     */
    private function groupBy(array $rows, string $keyField, string $valueField): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row[$keyField]][] = $row[$valueField];
        }

        return $grouped;
    }

    /**
     * Mirrors FormFactory::sampleFields() — one field of each kind.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sampleFields(): array
    {
        return [
            ['id' => (string) Str::uuid(), 'type' => 'text', 'label' => 'Your name', 'description' => null, 'required' => true, 'config' => ['placeholder' => 'Jane Doe']],
            ['id' => (string) Str::uuid(), 'type' => 'paragraph', 'label' => 'Tell us more', 'description' => 'Optional details.', 'required' => false, 'config' => ['placeholder' => '']],
            ['id' => (string) Str::uuid(), 'type' => 'date', 'label' => 'Preferred date', 'description' => null, 'required' => false, 'config' => ['include_time' => true]],
            ['id' => (string) Str::uuid(), 'type' => 'range', 'label' => 'How likely are you to recommend us?', 'description' => null, 'required' => false, 'config' => ['min' => 0, 'max' => 10, 'step' => 1, 'min_label' => 'Unlikely', 'max_label' => 'Very likely']],
            ['id' => (string) Str::uuid(), 'type' => 'list', 'label' => 'Which options apply?', 'description' => null, 'required' => false, 'config' => ['multiple' => true, 'items' => ['Email', 'Phone', 'SMS']]],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function answersFor(array $fields, bool $long): array
    {
        $answers = [];

        foreach ($fields as $field) {
            $id = $field['id'] ?? null;
            if (! is_string($id)) {
                continue;
            }
            $config = (array) ($field['config'] ?? []);
            $answers[$id] = match ($field['type'] ?? null) {
                'text' => $long ? $this->longBlob : 'Sample answer',
                'paragraph' => $long ? $this->longBlob : 'A longer sample answer.',
                'date' => '2026-06-24',
                'range' => $config['min'] ?? 0,
                'list' => ($config['multiple'] ?? false)
                    ? array_slice((array) ($config['items'] ?? []), 0, 1)
                    : (($config['items'] ?? [])[0] ?? null),
                default => null,
            };
        }

        return $answers;
    }
}
