<?php

namespace App\Console\Commands;

use App\Imports\ReviewsImport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

#[Signature('app:import-reviews')]
#[Description('Command to import the review data from the CSV file into the database')]
class ImportReviews extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = public_path('customer_reviews.csv');

        if (! is_file($filePath)) {
            $this->error("Review CSV file not found: {$filePath}");

            return self::FAILURE;
        }

        Excel::queueImport(new ReviewsImport, $filePath);

        $this->info('Review CSV import queued.');

        return self::SUCCESS;
    }
}
