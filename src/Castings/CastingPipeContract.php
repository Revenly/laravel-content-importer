<?php
namespace R64\ContentImport\Castings;

interface CastingPipeContract
{
    public function __invoke($content, array $concerns = []);

    public function pipe($content);
}
