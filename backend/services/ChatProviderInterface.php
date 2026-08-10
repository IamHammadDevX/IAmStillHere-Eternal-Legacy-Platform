<?php

interface ChatProviderInterface
{
    public function chat(array $messages, array $options = []): array;
}
