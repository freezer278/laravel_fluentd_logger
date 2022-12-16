<?php

namespace Vmorozov\LaravelFluentdLogger\Logs\Features;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DbQueryLog
{
    public function init(): void
    {
        DB::listen(function ($query) {
            Log::info(
                'DB Query',
                [
                    'query' => $query->sql,
// commented because there are elasticsearch problems with mapping some kinds of content inside bindings
//                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]
            );
        });
    }
}
