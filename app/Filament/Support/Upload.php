<?php

namespace App\Filament\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;

class Upload
{
    /** High quality photo upload with built-in crop / rotate editor. */
    public static function image(string $field, string $directory, string $label = 'Photo'): FileUpload
    {
        return static::keepExternalLinks(
            FileUpload::make($field)
                ->label($label)
                ->image()
                ->disk('public')
                ->directory($directory)
                ->visibility('public')
                ->imageEditor()
                ->maxSize(15360) // 15 MB
                ->imagePreviewHeight('220')
                ->helperText('JPG, PNG or WebP up to 15 MB. Use the pencil icon to crop.')
        );
    }

    public static function video(string $field, string $directory, string $label = 'Video file'): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
            ->maxSize(204800) // 200 MB
            ->helperText('MP4, WebM or MOV up to 200 MB.');
    }

    /**
     * Demo content points at photos hosted elsewhere (https://…). Without this the upload
     * field would treat them as missing files and wipe them the first time a record is saved.
     */
    protected static function keepExternalLinks(FileUpload $upload): FileUpload
    {
        $isExternal = fn (mixed $file): bool => is_string($file) && preg_match('~^https?://~', $file) === 1;

        return $upload
            ->fetchFileInformation(fn (BaseFileUpload $component): bool => ! collect(Arr::wrap($component->getRawState()))->contains($isExternal))
            ->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames) use ($isExternal): ?array {
                if (! $isExternal($file)) {
                    return $component->getUploadedFile($file, $storedFileNames);
                }

                return [
                    'name' => basename((string) parse_url($file, PHP_URL_PATH)) ?: 'photo',
                    'size' => 0,
                    'type' => 'image/jpeg',
                    'url' => $file,
                ];
            });
    }
}
