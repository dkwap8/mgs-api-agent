<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Laravel encrypted strings are much longer than the original
         * 11-digit NIN and BVN values.
         *
         * The columns must be changed to TEXT before encrypting and
         * updating the existing records.
         */
        Schema::table('business_activations', function (Blueprint $table): void {
            $table->text('nin')->nullable()->change();
            $table->text('bvn')->nullable()->change();
        });

        Schema::table('business_activations', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_activations', 'nin_hash')) {
                $table->string('nin_hash', 64)
                    ->nullable()
                    ->after('nin');

                $table->index('nin_hash');
            }

            if (! Schema::hasColumn('business_activations', 'bvn_hash')) {
                $table->string('bvn_hash', 64)
                    ->nullable()
                    ->after('bvn');

                $table->index('bvn_hash');
            }
        });

        DB::table('business_activations')
            ->select(['id', 'nin', 'bvn'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach (['nin', 'bvn'] as $field) {
                        $value = $row->{$field};

                        if (! is_string($value) || trim($value) === '') {
                            continue;
                        }

                        $plainValue = $value;
                        $alreadyEncrypted = false;

                        try {
                            $plainValue = Crypt::decryptString($value);
                            $alreadyEncrypted = true;
                        } catch (\Throwable) {
                            /*
                             * The value is still plain text.
                             * It will be encrypted below.
                             */
                        }

                        if (! $alreadyEncrypted) {
                            $updates[$field] = Crypt::encryptString($plainValue);
                        }

                        $updates[$field.'_hash'] = hash(
                            'sha256',
                            $plainValue
                        );
                    }

                    if ($updates !== []) {
                        DB::table('business_activations')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });

        Schema::table('api_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('api_keys', 'revoked_at')) {
                $table->timestamp('revoked_at')
                    ->nullable()
                    ->after('status');
            }

            if (! Schema::hasColumn('api_keys', 'last_rotated_at')) {
                $table->timestamp('last_rotated_at')
                    ->nullable()
                    ->after('last_used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('api_keys', 'last_rotated_at')) {
                $table->dropColumn('last_rotated_at');
            }

            if (Schema::hasColumn('api_keys', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });

        Schema::table('business_activations', function (Blueprint $table): void {
            if (Schema::hasColumn('business_activations', 'nin_hash')) {
                $table->dropIndex(['nin_hash']);
                $table->dropColumn('nin_hash');
            }

            if (Schema::hasColumn('business_activations', 'bvn_hash')) {
                $table->dropIndex(['bvn_hash']);
                $table->dropColumn('bvn_hash');
            }
        });

        /*
         * Do not change nin and bvn back to VARCHAR(11).
         *
         * The encrypted values are longer than 11 characters, so reducing
         * the columns would destroy the encrypted data.
         */
    }
};