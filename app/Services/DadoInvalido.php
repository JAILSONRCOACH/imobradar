<?php

namespace App\Services;

use RuntimeException;

/** Anúncio recebido sem os dados mínimos para ser gravado. */
class DadoInvalido extends RuntimeException {}
