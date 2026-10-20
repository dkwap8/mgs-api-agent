<?php

namespace App\Services;

use App\Models\BusinessActivation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivationDocumentService
{
    public const FIELDS = ['proof_of_address', 'cac_certificate', 'cac_status_report'];

    public function serve(BusinessActivation $activation, string $field, bool $download = false): BinaryFileResponse
    {
        abort_unless(in_array($field, self::FIELDS, true), 404, 'Document not found.');
        $stored = str_replace('\\', '/', (string) $activation->getAttribute($field));
        // Accept only known relative legacy prefixes, never URLs or user-chosen paths.
        $stored = preg_replace('#^(?:/?storage/|/?storage/app/public/|/?public/)#', '', $stored);
        abort_unless(is_string($stored) && str_starts_with($stored, 'activations/') && ! str_contains($stored, "\0"), 404, 'Document not found.');
        abort_if(in_array('..', explode('/', $stored), true), 404, 'Document not found.');

        $file = null;
        foreach ([\Illuminate\Support\Facades\Storage::disk('local')->path(''), \Illuminate\Support\Facades\Storage::disk('public')->path('')] as $root) {
            $realRoot = realpath($root);
            $candidate = realpath($root.'/'.$stored);
            if ($realRoot && $candidate && is_file($candidate)
                && str_starts_with(str_replace('\\', '/', $candidate), str_replace('\\', '/', $realRoot).'/')) {
                $file = $candidate;
                break;
            }
        }
        abort_unless($file, 404, 'The uploaded document is missing. Please contact support.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true), 415, 'This document type cannot be previewed.');
        $headers = ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        $name = $field.'.'.match ($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', default => 'pdf' };
        return $download ? response()->download($file, $name, $headers) : response()->file($file, $headers);
    }
}
