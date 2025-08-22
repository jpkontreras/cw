<?php

declare(strict_types=1);

namespace Colame\Settings\Enums;

enum SettingType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ARRAY = 'array';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case TIME = 'time';
    case FILE = 'file';
    case COLOR = 'color';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case ENCRYPTED = 'encrypted';

    public function cast(): string
    {
        return match ($this) {
            self::STRING, self::FILE, self::COLOR, self::SELECT, self::ENCRYPTED => 'string',
            self::INTEGER => 'integer',
            self::FLOAT => 'float',
            self::BOOLEAN => 'boolean',
            self::JSON, self::ARRAY, self::MULTISELECT => 'array',
            self::DATE => 'date',
            self::DATETIME => 'datetime',
            self::TIME => 'time',
        };
    }

    public function inputType(): string
    {
        return match ($this) {
            self::STRING => 'text',
            self::INTEGER, self::FLOAT => 'number',
            self::BOOLEAN => 'checkbox',
            self::JSON, self::ARRAY => 'textarea',
            self::DATE => 'date',
            self::DATETIME => 'datetime-local',
            self::TIME => 'time',
            self::FILE => 'file',
            self::COLOR => 'color',
            self::SELECT => 'select',
            self::MULTISELECT => 'select-multiple',
            self::ENCRYPTED => 'password',
        };
    }

    public function requiresOptions(): bool
    {
        return in_array($this, [self::SELECT, self::MULTISELECT], true);
    }

    public function isEncrypted(): bool
    {
        return $this === self::ENCRYPTED;
    }
}