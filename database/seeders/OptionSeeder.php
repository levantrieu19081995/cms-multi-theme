<?php

namespace Database\Seeders;

use App\Enums\OptionEnum;
use App\Models\Option;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    DB::transaction(function () {
      // Begin transaction
      // foreach (OptionEnum::cases() as $option) {
      //   $optionDto = $option->value();
      //   if (!Option::whereKey($optionDto->key)->exists()) {
      //     $model = new Option();
      //     $model->key = $optionDto->key;
      //     $model->value = $optionDto->defaultValue;
      //     $model->autoload = $optionDto->autoload;
      //     $model->save();
      //   }
      // }

      foreach (OptionEnum::cases() as $option) {
        $optionDto = $option->value();

        if (!Option::where('key', $optionDto->key)->exists()) {
          $model = new Option();
          $model->key = $optionDto->key;
          $model->value = $optionDto->defaultValue;
          $model->autoload = $optionDto->autoload;
          $model->save();
        }
      }
    });
  }
}
