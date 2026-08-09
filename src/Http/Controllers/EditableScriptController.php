<?php

namespace Arm092\LivewireDatatables\Http\Controllers;

use Illuminate\Http\Response;

class EditableScriptController
{
    public function __invoke(): Response
    {
        return response(
            file_get_contents(__DIR__.'/../../../resources/js/editable.js'),
            200,
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'public, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
