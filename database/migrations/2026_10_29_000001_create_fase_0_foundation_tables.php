<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Profil Instansi (Single-Tenant Config)
        if (! Schema::hasTable('institution_profiles')) {
            Schema::create('institution_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('Politeknik Negeri Demo');
                $table->string('alias')->default('POLTEK');
                $table->text('address')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('website')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('header_image_path')->nullable();
                $table->string('timezone')->default('Asia/Jakarta');
                $table->string('default_theme')->default('emerald');
                $table->json('terminology_dict')->nullable();
                $table->boolean('is_configured')->default(false);
                $table->timestamps();
            });
        }

        // 2. Pengaturan Sistem Key-Value
        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value');
                $table->timestamps();
            });
        }

        // 3. Unit Kerja Hierarkis
        if (! Schema::hasTable('org_units')) {
            Schema::create('org_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('org_units')->onDelete('set null');
                $table->string('name');
                $table->string('code')->unique();
                $table->string('type'); // pimpinan, jurusan, prodi, bagian, unit_pelaksana
                $table->integer('order_index')->default(0);
                $table->timestamps();
            });
        }

        // 4. Master Jabatan
        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('org_unit_id')->constrained('org_units')->onDelete('cascade');
                $table->string('name');
                $table->integer('level')->default(1);
                $table->boolean('is_penandatangan')->default(false);
                $table->timestamps();
            });
        }

        // 5. Penyesuaian Tabel Users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nip')) {
                $table->string('nip')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'position_id')) {
                $table->foreignId('position_id')->nullable()->after('password')->constrained('positions')->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
            if (! Schema::hasColumn('users', 'signature_image_path')) {
                $table->string('signature_image_path')->nullable();
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable();
            }
            // Pastikan kolom 'name' ada jika sebelumnya di-rename jadi 'nama'
            if (! Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'nama')) {
                $table->string('name')->nullable();
            }
        });

        // 6. Delegasi Wewenang / Plt
        if (! Schema::hasTable('position_delegations')) {
            Schema::create('position_delegations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('position_id')->constrained('positions')->onDelete('cascade');
                $table->foreignId('original_user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('delegate_user_id')->constrained('users')->onDelete('cascade');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('reason');
                $table->string('surat_tugas_number')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 7. Status Lisensi
        if (! Schema::hasTable('license_states')) {
            Schema::create('license_states', function (Blueprint $table) {
                $table->id();
                $table->string('license_key')->unique();
                $table->string('institution_slug');
                $table->string('plan')->default('standar'); // dasar, standar, lanjutan
                $table->string('status')->default('active'); // active, grace_period, expired
                $table->timestamp('expires_at');
                $table->integer('grace_period_days')->default(14);
                $table->integer('max_users')->default(100);
                $table->bigInteger('max_storage_bytes')->default(53687091200); // 50 GB
                $table->json('active_features')->nullable();
                $table->timestamp('last_verified_at');
                $table->text('signature_hash');
                $table->timestamps();
            });
        }

        // 8. Pencatatan Pemakaian Kuota (User & Storage)
        if (! Schema::hasTable('quota_usages')) {
            Schema::create('quota_usages', function (Blueprint $table) {
                $table->id();
                $table->timestamp('recorded_at');
                $table->integer('active_users_count')->default(0);
                $table->bigInteger('storage_used_bytes')->default(0);
                $table->bigInteger('files_count')->default(0);
                $table->json('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 9. Audit Log Append-Only
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('causer_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action')->index();
                $table->string('subject_type')->index();
                $table->bigInteger('subject_id')->index();
                $table->text('description');
                $table->json('properties')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Pasang Trigger Database Append-Only untuk activity_logs
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION prevent_activity_logs_modification()
                RETURNS TRIGGER AS \$\$
                BEGIN
                    RAISE EXCEPTION 'TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi UPDATE dan DELETE dilarang.';
                END;
                \$\$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_activity_logs_no_update_delete ON activity_logs;
                CREATE TRIGGER trg_activity_logs_no_update_delete
                BEFORE UPDATE OR DELETE ON activity_logs
                FOR EACH ROW
                EXECUTE FUNCTION prevent_activity_logs_modification();
            ");
        } elseif ($driver === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_activity_logs_no_update
                BEFORE UPDATE ON activity_logs
                BEGIN
                    SELECT RAISE(ABORT, 'TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi UPDATE dilarang.');
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_activity_logs_no_delete
                BEFORE DELETE ON activity_logs
                BEGIN
                    SELECT RAISE(ABORT, 'TABEL activity_logs BERSIFAT APPEND-ONLY: Operasi DELETE dilarang.');
                END;
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_activity_logs_no_update_delete ON activity_logs;');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_activity_logs_modification();');
        } elseif ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_activity_logs_no_update;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_activity_logs_no_delete;');
        }

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('quota_usages');
        Schema::dropIfExists('license_states');
        Schema::dropIfExists('position_delegations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropColumn(['nip', 'position_id', 'phone', 'is_active', 'signature_image_path', 'two_factor_secret']);
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('org_units');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('institution_profiles');
    }
};
