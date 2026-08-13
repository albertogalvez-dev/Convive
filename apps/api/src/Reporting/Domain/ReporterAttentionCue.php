<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ReporterAttentionCue: string
{
    case NeedsPromptAttention = 'needs_prompt_attention';
    case NoPromptAttentionIndicated = 'no_prompt_attention_indicated';
    case Unknown = 'unknown';
}
