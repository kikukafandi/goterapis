<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegalController extends Controller
{
    public function __invoke(string $document): View
    {
        $content = config("legal.documents.{$document}");

        if ($content === null) {
            throw new NotFoundHttpException;
        }

        return view('legal.show', [
            'document' => $content,
            'incomplete' => collect(['operator_name', 'operator_address', 'operator_email', 'version', 'effective_date'])
                ->contains(fn (string $key): bool => blank(config("legal.{$key}")) || str_contains(strtoupper((string) config("legal.{$key}")), 'DRAFT')),
        ]);
    }
}
