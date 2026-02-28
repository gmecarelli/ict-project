<?php

namespace Packages\IctInterface\Services;

use Illuminate\Support\Str;
use Packages\IctInterface\Contracts\FormActionHandler;

class ActionHandlerResolver
{
    public function resolve(?string $tableName): ?FormActionHandler
    {
        if (!$tableName) return null;

        // 1. Config esplicita: config('ict.action_handlers')['books'] => BookHandler::class
        $handlers = config('ict.action_handlers', []);
        if (isset($handlers[$tableName])) {
            return app($handlers[$tableName]);
        }

        // 2. Convention: App\Actions\{StudlyCase(tableName)}ActionHandler
        $class = 'App\\Actions\\' . Str::studly($tableName) . 'ActionHandler';
        if (class_exists($class)) {
            return app($class);
        }

        return null;
    }
}
