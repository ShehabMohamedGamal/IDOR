<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Support\IdorScenario;
use App\Support\IndirectReviewReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdorBookReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $alice;

    private User $bob;

    private Book $book;

    private Review $aliceReview;

    private Review $bobReview;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.idor_scenario' => IdorScenario::SAFE]);

        $this->admin = User::factory()->admin()->create();
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();

        $this->book = Book::factory()->create();

        $this->aliceReview = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $this->alice->id,
            'rating' => 4,
            'comment' => 'Alice review',
        ]);

        $this->bobReview = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $this->bob->id,
            'rating' => 2,
            'comment' => 'Bob review',
        ]);
    }

    public function test_secure_mode_blocks_cross_user_review_update(): void
    {
        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->bobReview, false), [
                'rating' => 5,
                'comment' => 'Compromised review',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Bob review',
        ]);
    }

    public function test_secure_mode_blocks_cross_user_profile_update(): void
    {
        $this->actingAs($this->alice)
            ->put(route('users.update', $this->bob, false), [
                'name' => 'Hacked Bob',
                'email' => 'hacked-bob@example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $this->bob->id,
            'name' => $this->bob->name,
        ]);
    }

    public function test_secure_mode_blocks_non_admin_book_mutation(): void
    {
        $this->actingAs($this->alice)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'Unauthorized Book',
                'author' => 'Attacker',
                'published_year' => 2024,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'title' => 'Unauthorized Book',
        ]);
    }

    public function test_vulnerable_mode_allows_cross_user_review_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::BASIC_ALL]);

        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->bobReview, false), [
                'rating' => 5,
                'comment' => 'Compromised review',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Compromised review',
        ]);
    }

    public function test_vulnerable_mode_allows_cross_user_profile_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::BASIC_ALL]);

        $this->actingAs($this->alice)
            ->put(route('users.update', $this->bob, false), [
                'name' => 'Hacked Bob',
                'email' => 'hacked-bob@example.com',
            ])
            ->assertRedirect(route('users.show', $this->bob, false));

        $this->assertDatabaseHas('users', [
            'id' => $this->bob->id,
            'name' => 'Hacked Bob',
            'email' => 'hacked-bob@example.com',
        ]);
    }

    public function test_vulnerable_mode_allows_non_admin_book_mutation(): void
    {
        config(['security.idor_scenario' => IdorScenario::BASIC_ALL]);

        $this->actingAs($this->alice)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'Unauthorized Book',
                'author' => 'Attacker',
                'published_year' => 2024,
            ])
            ->assertRedirect(route('admin.books.index', absolute: false));

        $this->assertDatabaseHas('books', [
            'title' => 'Unauthorized Book',
            'author' => 'Attacker',
        ]);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->post(route('reviews.store', $this->book, false), [
            'rating' => 5,
            'comment' => 'Guest review',
        ])->assertRedirect(route('login', absolute: false));

        $this->get(route('reviews.edit', $this->aliceReview, false))
            ->assertRedirect(route('login', absolute: false));

        $this->put(route('users.update', $this->alice, false), [
            'name' => 'Nope',
            'email' => 'nope@example.com',
        ])->assertRedirect(route('login', absolute: false));
    }

    public function test_owner_can_update_own_review_in_secure_mode(): void
    {
        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->aliceReview, false), [
                'rating' => 3,
                'comment' => 'Edited by owner',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->aliceReview->id,
            'comment' => 'Edited by owner',
            'rating' => 3,
        ]);
    }

    public function test_admin_can_manage_books_in_secure_mode(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'published_year' => 2008,
            ])
            ->assertRedirect(route('admin.books.index', absolute: false));

        $created = Book::query()->where('title', 'Clean Code')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.books.update', $created, false), [
                'title' => 'Clean Code 2nd Edition',
                'author' => 'Robert C. Martin',
                'published_year' => 2024,
            ])
            ->assertRedirect(route('admin.books.index', absolute: false));

        $this->assertDatabaseHas('books', [
            'id' => $created->id,
            'title' => 'Clean Code 2nd Edition',
        ]);
    }

    public function test_profile_update_only_mode_allows_cross_user_profile_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::PROFILE_UPDATE_ONLY]);

        $this->actingAs($this->alice)
            ->put(route('users.update', $this->bob, false), [
                'name' => 'Scenario Bob',
                'email' => 'scenario-bob@example.com',
            ])
            ->assertRedirect(route('users.show', $this->alice, false));

        $this->assertDatabaseHas('users', [
            'id' => $this->bob->id,
            'name' => 'Scenario Bob',
            'email' => 'scenario-bob@example.com',
        ]);
    }

    public function test_profile_update_only_mode_still_blocks_cross_user_review_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::PROFILE_UPDATE_ONLY]);

        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->bobReview, false), [
                'rating' => 5,
                'comment' => 'Should still fail',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Bob review',
        ]);
    }

    public function test_profile_update_only_mode_still_blocks_non_admin_book_mutation(): void
    {
        config(['security.idor_scenario' => IdorScenario::PROFILE_UPDATE_ONLY]);

        $this->actingAs($this->alice)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'Should Not Create',
                'author' => 'Scenario Attacker',
                'published_year' => 2024,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'title' => 'Should Not Create',
        ]);
    }

    public function test_hidden_params_review_store_mode_allows_impersonation_via_form_payload(): void
    {
        config(['security.idor_scenario' => IdorScenario::HIDDEN_PARAMS_REVIEW_STORE]);

        $this->actingAs($this->alice)
            ->post(route('reviews.store', $this->book, false), [
                'rating' => 5,
                'comment' => 'Injected hidden field',
                'user_id' => $this->bob->id,
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'book_id' => $this->book->id,
            'user_id' => $this->bob->id,
            'comment' => 'Injected hidden field',
            'rating' => 5,
        ]);
    }

    public function test_hidden_params_review_store_mode_allows_impersonation_via_json_payload(): void
    {
        config(['security.idor_scenario' => IdorScenario::HIDDEN_PARAMS_REVIEW_STORE]);

        $this->actingAs($this->alice)
            ->postJson(route('reviews.store', $this->book, false), [
                'rating' => 1,
                'comment' => 'Injected JSON field',
                'user_id' => $this->bob->id,
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('reviews', [
            'book_id' => $this->book->id,
            'user_id' => $this->bob->id,
            'comment' => 'Injected JSON field',
            'rating' => 1,
        ]);
    }

    public function test_hidden_params_review_store_mode_still_blocks_cross_user_profile_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::HIDDEN_PARAMS_REVIEW_STORE]);

        $this->actingAs($this->alice)
            ->put(route('users.update', $this->bob, false), [
                'name' => 'Should Stay Safe',
                'email' => 'stay-safe@example.com',
            ])
            ->assertForbidden();
    }

    public function test_hidden_params_review_store_mode_still_blocks_non_admin_book_mutation(): void
    {
        config(['security.idor_scenario' => IdorScenario::HIDDEN_PARAMS_REVIEW_STORE]);

        $this->actingAs($this->alice)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'Hidden Param Attempt',
                'author' => 'Attacker',
                'published_year' => 2024,
            ])
            ->assertForbidden();
    }

    public function test_safe_mode_ignores_hidden_user_id_parameter_in_review_store(): void
    {
        config(['security.idor_scenario' => IdorScenario::SAFE]);

        $this->actingAs($this->alice)
            ->post(route('reviews.store', $this->book, false), [
                'rating' => 5,
                'comment' => 'Should belong to Alice',
                'user_id' => $this->bob->id,
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'book_id' => $this->book->id,
            'user_id' => $this->alice->id,
            'comment' => 'Should belong to Alice',
            'rating' => 5,
        ]);
    }

    public function test_indirect_refs_mode_allows_cross_user_update_via_filename_reference(): void
    {
        config(['security.idor_scenario' => IdorScenario::INDIRECT_REFS_REVIEW_UPDATE]);
        $reference = IndirectReviewReference::filename($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.indirect.update', ['reference' => $reference], false), [
                'rating' => 5,
                'comment' => 'Updated via filename reference',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Updated via filename reference',
            'rating' => 5,
        ]);
    }

    public function test_indirect_refs_mode_allows_cross_user_update_via_hash_reference(): void
    {
        config(['security.idor_scenario' => IdorScenario::INDIRECT_REFS_REVIEW_UPDATE]);
        $reference = IndirectReviewReference::hash($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.indirect.update', ['reference' => $reference], false), [
                'rating' => 1,
                'comment' => 'Updated via hash reference',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Updated via hash reference',
            'rating' => 1,
        ]);
    }

    public function test_indirect_refs_mode_allows_cross_user_update_via_encoded_reference(): void
    {
        config(['security.idor_scenario' => IdorScenario::INDIRECT_REFS_REVIEW_UPDATE]);
        $reference = IndirectReviewReference::encoded($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.indirect.update', ['reference' => $reference], false), [
                'rating' => 2,
                'comment' => 'Updated via encoded reference',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Updated via encoded reference',
            'rating' => 2,
        ]);
    }

    public function test_indirect_refs_mode_still_blocks_direct_review_update_idor(): void
    {
        config(['security.idor_scenario' => IdorScenario::INDIRECT_REFS_REVIEW_UPDATE]);

        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->bobReview, false), [
                'rating' => 5,
                'comment' => 'Direct route should still fail',
            ])
            ->assertForbidden();
    }

    public function test_safe_mode_blocks_indirect_reference_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::SAFE]);
        $reference = IndirectReviewReference::filename($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.indirect.update', ['reference' => $reference], false), [
                'rating' => 5,
                'comment' => 'Should be blocked',
            ])
            ->assertForbidden();
    }

    public function test_uuid_mode_allows_cross_user_update_via_uuid_reference(): void
    {
        config(['security.idor_scenario' => IdorScenario::UUID_REVIEW_UPDATE]);
        $uuid = IndirectReviewReference::uuid($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.uuid.update', ['uuid' => $uuid], false), [
                'rating' => 4,
                'comment' => 'Updated via UUID reference',
            ])
            ->assertRedirect(route('books.show', $this->book, false));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->bobReview->id,
            'comment' => 'Updated via UUID reference',
            'rating' => 4,
        ]);
    }

    public function test_uuid_mode_still_blocks_direct_review_update_idor(): void
    {
        config(['security.idor_scenario' => IdorScenario::UUID_REVIEW_UPDATE]);

        $this->actingAs($this->alice)
            ->put(route('reviews.update', $this->bobReview, false), [
                'rating' => 5,
                'comment' => 'Direct route should still fail',
            ])
            ->assertForbidden();
    }

    public function test_safe_mode_blocks_uuid_reference_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::SAFE]);
        $uuid = IndirectReviewReference::uuid($this->bobReview->id);

        $this->actingAs($this->alice)
            ->put(route('reviews.uuid.update', ['uuid' => $uuid], false), [
                'rating' => 5,
                'comment' => 'Should be blocked',
            ])
            ->assertForbidden();
    }

    public function test_uuid_mode_still_blocks_cross_user_profile_update(): void
    {
        config(['security.idor_scenario' => IdorScenario::UUID_REVIEW_UPDATE]);

        $this->actingAs($this->alice)
            ->put(route('users.update', $this->bob, false), [
                'name' => 'Should Stay Safe',
                'email' => 'still-safe@example.com',
            ])
            ->assertForbidden();
    }

    public function test_uuid_mode_still_blocks_non_admin_book_mutation(): void
    {
        config(['security.idor_scenario' => IdorScenario::UUID_REVIEW_UPDATE]);

        $this->actingAs($this->alice)
            ->post(route('admin.books.store', absolute: false), [
                'title' => 'UUID Scenario Attempt',
                'author' => 'Attacker',
                'published_year' => 2024,
            ])
            ->assertForbidden();
    }
}
