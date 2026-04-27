<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $users = User::factory(5)->create();
        $books = Book::factory(8)->create();

        foreach ($books as $book) {
            $reviewers = $users->random(min(3, $users->count()));

            foreach ($reviewers as $reviewer) {
                Review::factory()->create([
                    'book_id' => $book->id,
                    'user_id' => $reviewer->id,
                ]);
            }
        }

        // Keep admin account explicit for demos.
        $admin->refresh();
    }
}
