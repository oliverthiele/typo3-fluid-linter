<?php

declare(strict_types=1);

namespace OliverThiele\FluidLinter\Result;

enum FixStatus
{
    case Applied;
    case Skipped;
    case None;
}