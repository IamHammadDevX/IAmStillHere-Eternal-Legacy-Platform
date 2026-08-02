<?php

class EnvironmentValidator
{
    private array $requiredVariables;

    public function __construct(?array $requiredVariables = null)
    {
        $this->requiredVariables = $requiredVariables ?: [
            'APP_ENV',
            'APP_URL',
            'APP_DEBUG',
            'DB_DRIVER',
            'DB_HOST',
            'DB_NAME',
            'DB_USER',
        ];
    }

    public function validate(): array
    {
        $missing = [];

        foreach ($this->requiredVariables as $variable) {
            $value = getenv($variable);
            if ($value === false || $value === '') {
                $missing[] = $variable;
            }
        }

        return [
            'valid' => count($missing) === 0,
            'missing' => $missing,
        ];
    }

    public function assertValid(): void
    {
        $result = $this->validate();

        if (!$result['valid']) {
            throw new RuntimeException('Missing required environment variables: ' . implode(', ', $result['missing']));
        }
    }
}
