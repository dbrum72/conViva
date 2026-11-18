<?php

namespace App\Modules\History;

use Tests\Feature\HistoryTest;

// Delegate to PHP unless the current test simulates a full temporary volume.
function tmpfile()
{
    return HistoryTest::$rejectTemporaryFile ? false : \tmpfile();
}
