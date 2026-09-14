<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Reference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pencarian jurnal memakai CrossRef. HTTP dipalsukan di sini supaya tes tidak
 * menembak jaringan sungguhan — lambat dan hasilnya berubah-ubah.
 */
class JournalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'email_verified_at' => now(),
        ]);
    }

    /** Satu karya CrossRef, dikurangi field yang tidak dipakai. */
    private function crossrefItem(string $doi = '10.1000/abc', string $title = 'AI Adoption in SMEs'): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'author' => [
                ['family' => 'Wendt', 'given' => 'Donnie'],
                ['family' => 'Bhalekar', 'given' => 'Sandeep'],
            ],
            'issued' => ['date-parts' => [[2025]]],
            'container-title' => ['Journal of Information Systems'],
            'publisher' => 'Apress',
            'type' => 'journal-article',
            'volume' => '12',
            'issue' => '3',
            'page' => '45-60',
            'URL' => "https://doi.org/{$doi}",
        ];
    }

    public function test_search_page_renders_results_from_crossref(): void
    {
        Http::fake([
            'api.crossref.org/*' => Http::response([
                'message' => ['items' => [$this->crossrefItem()]],
            ]),
        ]);

        $this->actingAs($this->user)
            ->get('/references/search?q=AI+adoption')
            ->assertOk()
            ->assertSee('AI Adoption in SMEs')
            ->assertSee('Wendt, Donnie; Bhalekar, Sandeep')
            ->assertSee('10.1000/abc');
    }

    public function test_search_page_renders_without_query_and_stays_offline(): void
    {
        // Tanpa kata kunci tidak boleh ada panggilan keluar sama sekali.
        Http::fake();

        $this->actingAs($this->user)
            ->get('/references/search')
            ->assertOk()
            ->assertSee('Belum ada pencarian');

        Http::assertNothingSent();
    }

    public function test_saving_a_result_creates_a_reference_with_crossref_metadata(): void
    {
        Http::fake([
            'api.crossref.org/*' => Http::response(['message' => $this->crossrefItem()]),
        ]);

        $this->actingAs($this->user)
            ->post('/references/import-doi', ['doi' => 'https://doi.org/10.1000/abc'])
            ->assertRedirect();

        $reference = Reference::sole();

        $this->assertSame($this->user->id, $reference->user_id);
        $this->assertSame('10.1000/abc', $reference->doi);
        $this->assertSame('AI Adoption in SMEs', $reference->title);
        $this->assertSame(2025, $reference->year);
        $this->assertSame('journal', $reference->type);
        $this->assertSame('Journal of Information Systems', $reference->container);
    }

    public function test_saving_the_same_doi_twice_keeps_one_row(): void
    {
        Http::fake([
            'api.crossref.org/*' => Http::response(['message' => $this->crossrefItem()]),
        ]);

        $this->actingAs($this->user)->post('/references/import-doi', ['doi' => '10.1000/abc']);
        $this->actingAs($this->user)->post('/references/import-doi', ['doi' => '10.1000/abc']);

        $this->assertDatabaseCount('references', 1);
    }

    public function test_unknown_doi_reports_an_error_instead_of_saving(): void
    {
        Http::fake(['api.crossref.org/*' => Http::response('not found', 404)]);

        $this->actingAs($this->user)
            ->post('/references/import-doi', ['doi' => '10.9999/tidak-ada'])
            ->assertSessionHasErrors('doi');

        $this->assertDatabaseCount('references', 0);
    }

    public function test_crossref_failure_does_not_break_the_page(): void
    {
        Http::fake(['api.crossref.org/*' => Http::response('boom', 500)]);

        $this->actingAs($this->user)
            ->get('/references/search?q=apa+saja')
            ->assertOk()
            ->assertSee('Tidak ada hasil');
    }

    public function test_cannot_attach_a_reference_to_someone_elses_project(): void
    {
        Http::fake([
            'api.crossref.org/*' => Http::response(['message' => $this->crossrefItem()]),
        ]);

        $other = User::factory()->create(['email_verified_at' => now()]);
        $theirProject = Project::create(['user_id' => $other->id, 'name' => 'Punya orang lain']);

        $this->actingAs($this->user)
            ->post('/references/import-doi', [
                'doi' => '10.1000/abc',
                'project_id' => $theirProject->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('references', 0);
    }

    public function test_cannot_save_into_another_users_project_via_the_reference_form(): void
    {
        $other = User::factory()->create(['email_verified_at' => now()]);
        $theirProject = Project::create(['user_id' => $other->id, 'name' => 'Punya orang lain']);

        $this->actingAs($this->user)
            ->post('/references', [
                'title' => 'Referensi selundupan',
                'type' => 'journal',
                'project_id' => $theirProject->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('references', 0);
    }

    public function test_search_page_requires_login(): void
    {
        $this->get('/references/search')->assertRedirect('/login');
    }
}
