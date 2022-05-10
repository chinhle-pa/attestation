<?php

namespace ChinhlePa\Attestation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attestation extends Model
{
  use HasFactory;

  // Disable Laravel's mass assignment protection
  protected $guarded = ['id'];
  public $table = 'attestations';

  /**
   * The "booting" method of the model.
   *
   * @return void
   */
  public static function boot()
  {
      parent::boot();

      static::creating(function ($attestation) {
          if ($attestation->expires_at === null) {
              $attestation->expires_at = now()->addMinutes(config('attestation.CHALLENGE_EXPIRE_MINUTES', 3));
          }
      });
  }
}