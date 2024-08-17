<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountLogs;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class AccountLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::chunk(1000,function (Collection $collection){
            $collection->each(function (Account $account) {
                AccountLogs::factory()->count(rand(10,20))->create([
                    'account_id' => $account->id,
                ]);
            });
        });
    }
}
