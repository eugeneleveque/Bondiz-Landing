<?php

namespace App\Partner;

/**
 * Chiffres du tableau de bord partenaire, calculés à partir des activités
 * renvoyées par Strapi (date de début, places, nombre de réservations).
 *
 * Une activité compte pour le mois (ou la semaine) où elle commence.
 */
class PartnerDashboard
{
    public const TIMEZONE = 'Europe/Paris';
    public const PERIODES = [4, 8, 12];

    private const MOIS_COURTS = ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
    private const MOIS_ABREGES = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    private const JOURS_COURTS = ['Dim.', 'Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.'];

    /**
     * @param list<array<string, mixed>> $activities
     *
     * @return array<string, mixed>
     */
    public function build(array $activities, int $semaines = 4, ?\DateTimeImmutable $now = null): array
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $now = ($now ?? new \DateTimeImmutable('now'))->setTimezone($tz);

        $dated = [];
        foreach ($activities as $activity) {
            if (!empty($activity['startDate'])) {
                $dated[] = $activity + ['debut' => (new \DateTimeImmutable($activity['startDate']))->setTimezone($tz)];
            }
        }

        $moisCourant = $now->modify('first day of this month')->setTime(0, 0);
        $moisPrecedent = $moisCourant->modify('-1 month');
        $courant = $this->entre($dated, $moisCourant, $moisCourant->modify('+1 month'));
        $precedent = $this->entre($dated, $moisPrecedent, $moisCourant);

        $participants = $this->reservations($courant);
        $remplissage = $this->remplissage($courant);

        return [
            'indicateurs' => [
                'participants' => [
                    'valeur' => $participants,
                    'evolution' => $this->evolution($participants, $this->reservations($precedent)),
                ],
                'activites' => [
                    'valeur' => \count($courant),
                    'evolution' => $this->evolution(\count($courant), \count($precedent)),
                ],
                'remplissage' => [
                    'valeur' => $remplissage,
                    'evolution' => null === $remplissage ? null : $this->evolution($remplissage, $this->remplissage($precedent)),
                ],
            ],
            'participation' => $this->parSemaine($dated, $now, $semaines),
            'reservationsParMois' => $this->parMois($dated, $moisCourant),
            'prochaines' => $this->prochaines($dated, $now),
        ];
    }

    /** Format d'affichage des prochaines activités : « Sam. 10 oct 2026 - 14h00 ». */
    public static function dateCourte(\DateTimeImmutable $date): string
    {
        return \sprintf(
            '%s %d %s %s - %s',
            self::JOURS_COURTS[(int) $date->format('w')],
            (int) $date->format('j'),
            rtrim(self::MOIS_ABREGES[(int) $date->format('n') - 1], '.'),
            $date->format('Y'),
            $date->format('H\hi'),
        );
    }

    private function entre(array $activities, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return array_values(array_filter($activities, static fn (array $a) => $a['debut'] >= $debut && $a['debut'] < $fin));
    }

    private function reservations(array $activities): int
    {
        return array_sum(array_map(static fn (array $a) => (int) ($a['bookingsCount'] ?? 0), $activities));
    }

    /** Part des places réservées, sur les activités qui ont un nombre de places maximum. */
    private function remplissage(array $activities): ?int
    {
        $avecPlaces = array_filter($activities, static fn (array $a) => ($a['maxParticipants'] ?? 0) > 0);
        $places = array_sum(array_map(static fn (array $a) => (int) $a['maxParticipants'], $avecPlaces));

        return $places > 0 ? (int) round($this->reservations($avecPlaces) / $places * 100) : null;
    }

    /** Évolution en pourcentage ; null quand le mois précédent est vide. */
    private function evolution(int $courant, ?int $precedent): ?int
    {
        if (null === $precedent || 0 === $precedent) {
            return null;
        }

        return (int) round(($courant - $precedent) / $precedent * 100);
    }

    private function parSemaine(array $activities, \DateTimeImmutable $now, int $semaines): array
    {
        $lundi = $now->modify('monday this week')->setTime(0, 0);
        $points = [];
        for ($i = $semaines - 1; $i >= 0; --$i) {
            $debut = $lundi->modify(\sprintf('-%d weeks', $i));
            $points[] = [
                'libelle' => \sprintf('%d %s', (int) $debut->format('j'), self::MOIS_ABREGES[(int) $debut->format('n') - 1]),
                'valeur' => $this->reservations($this->entre($activities, $debut, $debut->modify('+1 week'))),
            ];
        }

        return $this->graphique($points);
    }

    private function parMois(array $activities, \DateTimeImmutable $moisCourant): array
    {
        $points = [];
        for ($i = 5; $i >= 0; --$i) {
            $debut = $moisCourant->modify(\sprintf('-%d months', $i));
            $points[] = [
                'libelle' => self::MOIS_COURTS[(int) $debut->format('n') - 1],
                'valeur' => $this->reservations($this->entre($activities, $debut, $debut->modify('+1 month'))),
            ];
        }

        return $this->graphique($points);
    }

    /**
     * Échelle de l'axe vertical (5 graduations, pas d'au moins 10, comme la
     * maquette) et position de chaque point en pourcentage de la hauteur.
     */
    private function graphique(array $points): array
    {
        $max = max(array_column($points, 'valeur') ?: [0]);
        $pas = 10;
        foreach ([10, 20, 25, 50, 100, 200, 250, 500, 1000] as $candidat) {
            $pas = $candidat;
            if ($candidat * 4 >= $max) {
                break;
            }
        }
        $echelle = $pas * 4;

        foreach ($points as $i => $point) {
            $points[$i]['hauteur'] = round(min($point['valeur'], $echelle) / $echelle * 100, 2);
        }

        return [
            'points' => $points,
            'graduations' => array_reverse(range(0, $echelle, $pas)),
        ];
    }

    private function prochaines(array $activities, \DateTimeImmutable $now): array
    {
        $avenir = array_filter($activities, static fn (array $a) => $a['debut'] >= $now);
        usort($avenir, static fn (array $a, array $b) => $a['debut'] <=> $b['debut']);

        return array_map(static fn (array $a) => $a + ['dateCourte' => self::dateCourte($a['debut'])], \array_slice($avenir, 0, 3));
    }
}
