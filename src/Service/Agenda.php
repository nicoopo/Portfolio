<?php

namespace App\Service;

use Symfony\UX\CalendarLink\CalendarEvent;

/**
 * Événement d'agenda au format iCalendar (.ics, RFC 5545) : entretien d'une candidature (admin)
 * ou créneau réservé par un recruteur (/rendez-vous). Heure de Paris telle que saisie, une heure.
 */
final class Agenda
{
    public function ics(string $uid, \DateTimeImmutable $debut, string $titre, string $description = ''): string
    {
        $texte = fn (string $t) => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $t);

        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//nicolascataluna.fr//portfolio//FR', 'BEGIN:VEVENT',
            'UID:'.$uid.'@nicolascataluna.fr',
            'DTSTAMP:'.gmdate('Ymd\THis\Z'),
            'DTSTART;TZID=Europe/Paris:'.$debut->format('Ymd\THis'),
            'DTEND;TZID=Europe/Paris:'.$debut->modify('+1 hour')->format('Ymd\THis'),
            'SUMMARY:'.$texte($titre),
            'DESCRIPTION:'.$texte($description),
            'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:Entretien dans une heure', 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);
    }

    /**
     * Le même entretien pour les liens « Ajouter à Google Agenda / Outlook » (ux_calendar_link), qui passent l'heure en UTC.
     * L'heure est saisie à Paris mais relue en UTC (fuseau de PHP) : on la rattache à Paris avant, sinon 1 à 2 h de décalage.
     */
    public function evenement(\DateTimeImmutable $debut, string $titre, string $description = ''): CalendarEvent
    {
        $debut = new \DateTimeImmutable($debut->format('Y-m-d H:i:s'), new \DateTimeZone('Europe/Paris'));

        return new CalendarEvent($titre, $debut, $debut->modify('+1 hour'), '' !== $description ? $description : null);
    }
}
