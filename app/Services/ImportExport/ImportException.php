<?php

namespace App\Services\ImportExport;

use RuntimeException;

/**
 * A spreadsheet cannot be read: wrong type, missing header, or over the size or row cap.
 */
final class ImportException extends RuntimeException {}
