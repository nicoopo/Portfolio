<?php

namespace App\Service;

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
}
