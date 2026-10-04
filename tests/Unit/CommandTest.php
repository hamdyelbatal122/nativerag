<?php

declare(strict_types=1);

namespace Hamzi\NativeRag\Tests\Unit;

use Hamzi\NativeRag\Contracts\EmbeddableContract;
use Hamzi\NativeRag\Tests\TestCase;
use Hamzi\NativeRag\Traits\Embeddable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class CommandTestArticle extends Model implements EmbeddableContract
{
    use Embeddable;

    protected $table = 'command_test_articles';
    protected $guarded = [];

    public function toEmbeddableString(): string
    {
        return $this->title."\n".$this->body;
    }
}

class NonEmbeddableModel extends Model
{
    protected $table = 'command_test_articles';
}

class CommandTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('command_test_articles', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function test_health_check_command_success(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                ],
            ], 200),
        ]);

        $this->artisan('nativerag:health')
            ->expectsOutputToContain('NativeRAG Health & Diagnostics')
            ->expectsOutputToContain('All diagnostics passed.')
            ->assertExitCode(0);
    }

    public function test_health_check_command_failure_when_driver_down(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([], 500),
        ]);

        $this->artisan('nativerag:health')
            ->expectsOutputToContain('NativeRAG Health & Diagnostics')
            ->assertExitCode(1);
    }

    public function test_index_command_validates_class_existence(): void
    {
        $this->artisan('nativerag:index', ['model' => 'NonExistentClass'])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }

    public function test_index_command_validates_embeddable_contract(): void
    {
        $this->artisan('nativerag:index', ['model' => NonEmbeddableModel::class])
            ->expectsOutputToContain('must be an Eloquent Model implementing EmbeddableContract')
            ->assertExitCode(1);
    }

    public function test_index_command_warns_when_no_records_found(): void
    {
        $this->artisan('nativerag:index', ['model' => CommandTestArticle::class])
            ->expectsOutputToContain('No records found')
            ->assertExitCode(0);
    }

    public function test_index_command_indexes_records_successfully(): void
    {
        Http::fake([
            'http://localhost:11434/api/embed' => Http::response([
                'embeddings' => [
                    [0.1, 0.2, 0.3],
                ],
            ], 200),
        ]);

        CommandTestArticle::create([
            'title' => 'Article 1',
            'body' => 'Body of article 1',
        ]);

        CommandTestArticle::create([
            'title' => 'Article 2',
            'body' => 'Body of article 2',
        ]);

        $this->artisan('nativerag:index', [
            'model' => CommandTestArticle::class,
            '--force' => true,
        ])
            ->expectsOutputToContain('Indexing 2 records')
            ->expectsOutputToContain('Successfully processed 2 records')
            ->assertExitCode(0);
    }
}
