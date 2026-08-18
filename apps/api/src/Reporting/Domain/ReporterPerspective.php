<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

/**
 * Whether the person writing lived the situation or saw it happen to someone
 * else.
 *
 * This is not part of {@see TriageTaxonomy}. The taxonomy describes the
 * situation; this describes the relationship between the writer and the
 * situation, which is a different kind of fact and must not be folded into a
 * neutral severity vocabulary. Keeping it separate is what lets the witness
 * channel exist without changing what any existing taxonomy value means.
 *
 * There is deliberately no `Unknown`: the entry point the reporter used
 * determines this, so it is always a known fact rather than something they
 * are asked to declare.
 *
 * It carries no weight of its own. A witness account is not more or less
 * credible, urgent or serious than a first-person one -- it is read
 * differently, which is precisely why a professional must be able to see
 * which one they are reading.
 */
enum ReporterPerspective: string
{
    /** The reporter is describing something that happened to them. */
    case Experienced = 'experienced';

    /** The reporter saw or heard about it happening to someone else. */
    case Witnessed = 'witnessed';
}
