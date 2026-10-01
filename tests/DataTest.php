<?php

namespace App\Tests;

use App\Data\Competences;
use App\Data\Projets;
use PHPUnit\Framework\TestCase;

final class DataTest extends TestCase
{
    public function testLesProjetsCitentDesCompetencesExistantes(): void
    {
        $competences = array_merge(...array_column(Competences::CATEGORIES, 'competences'));

        foreach (Projets::CATEGORIES as $projets) {
            foreach ($projets as $projet) {
                foreach ($projet['competences'] as $competence) {
                    self::assertContains($competence, $competences, "« {$projet['titre']} » cite une compétence inconnue");
                }
            }
        }
    }

    public function testLesSlugsDeProjetsSontUniques(): void
    {
        $slugs = array_column(array_merge(...array_values(Projets::CATEGORIES)), 'slug');

        self::assertSame($slugs, array_unique($slugs));
    }
}
