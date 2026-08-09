<?php

interface EmbeddingProviderInterface
{
    /** @return array<int, array<int, float>> */
    public function embed(array $texts): array;
}
