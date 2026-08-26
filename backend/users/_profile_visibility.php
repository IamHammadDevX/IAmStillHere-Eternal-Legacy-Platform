<?php

function public_profile_section_keys()
{
    return [
        'posts',
        'ai_avatar',
        'autobiography',
        'about',
        'friends',
        'family',
        'journeys',
        'photos',
        'videos',
        'timeline',
        'tributes',
        'events',
    ];
}

function normalize_public_profile_sections($value)
{
    $allowed = public_profile_section_keys();

    if ($value === null || $value === '') {
        return $allowed;
    }

    if (!is_array($value)) {
        $decoded = json_decode((string) $value, true);
        if (!is_array($decoded)) {
            return $allowed;
        }
        $value = $decoded;
    }

    $selected = [];
    foreach ($allowed as $key) {
        if (in_array($key, $value, true)) {
            $selected[] = $key;
        }
    }

    return $selected;
}

function validate_public_profile_sections_input($value)
{
    $decoded = json_decode((string) $value, true);
    if (!is_array($decoded)) {
        throw new InvalidArgumentException('Public Profile feature selection is invalid.');
    }

    $allowed = public_profile_section_keys();
    foreach ($decoded as $key) {
        if (!is_string($key) || !in_array($key, $allowed, true)) {
            throw new InvalidArgumentException('Public Profile feature selection contains an unknown section.');
        }
    }

    return normalize_public_profile_sections($decoded);
}
