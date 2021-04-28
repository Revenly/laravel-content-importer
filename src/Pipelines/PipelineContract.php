<?php
namespace R64\ContentImport\Pipelines;

interface PipelineContract
{
    public function __invoke($content, array $concerns = []);

    public function pipe($content);
}
