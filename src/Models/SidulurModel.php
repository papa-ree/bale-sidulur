<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model baca-saja pada koneksi tenant `sidulur`.
 *
 * Tidak memakai cast terenkripsi: APP_KEY bale-front berbeda dengan admin,
 * sehingga kolom PII tidak dapat didekripsi di sini.
 */
abstract class SidulurModel extends Model
{
    protected $connection = 'sidulur';

    public $incrementing = false;

    protected $keyType = 'string';
}
