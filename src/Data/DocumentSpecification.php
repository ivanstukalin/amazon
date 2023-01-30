<?php

namespace App\Data;

class DocumentSpecification
{
    public string $format;
    public array  $size;
    public string $dpi;
    public string $pageLayout;
    public string $needFileJoining;
    public array  $requestedDocumentTypes;
}