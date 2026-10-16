<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Image d'aperçu des liens partagés (og:image, 1200 × 630) : ciel étoilé, surtitre, titre, pastilles de technos,
 * nom du site. Dessinée avec GD (FreeType : docker/php/Dockerfile*), police DejaVu fournie avec Dompdf.
 */
final class ImagePartage
{
    public const LARGEUR = 1200;
    public const HAUTEUR = 630;
    private const MARGE = 80;

    public function __construct(
        #[Autowire('%kernel.project_dir%/vendor/dompdf/dompdf/lib/fonts')] private readonly string $polices,
    ) {
    }

    /** @param list<string> $technos */
    public function png(string $surtitre, string $titre, array $technos): string
    {
        $image = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);
        imagealphablending($image, true);
        $this->ciel($image, crc32($titre));

        $gras = $this->polices.'/DejaVuSans-Bold.ttf';
        $normal = $this->polices.'/DejaVuSans.ttf';
        $blanc = imagecolorallocate($image, 245, 245, 245);
        $cyan = imagecolorallocate($image, 0, 212, 255);
        $violet = imagecolorallocate($image, 127, 90, 240);

        $y = 170;
        imagettftext($image, 26, 0, self::MARGE, $y, $cyan, $gras, mb_strtoupper($surtitre));

        // Titre : 3 lignes au plus, police réduite si le titre est long
        $taille = mb_strlen($titre) > 40 ? 52 : 64;
        foreach (\array_slice($this->lignes($titre, $gras, $taille, self::LARGEUR - 2 * self::MARGE), 0, 3) as $ligne) {
            $y += (int) ($taille * 1.35);
            imagettftext($image, $taille, 0, self::MARGE, $y, $blanc, $gras, $ligne);
        }

        // Pastilles des technos, sur une ligne
        $x = self::MARGE;
        $y = self::HAUTEUR - 130;
        foreach ($technos as $techno) {
            $boite = imagettfbbox(22, 0, $normal, $techno);
            $largeur = $boite[2] - $boite[0] + 36;
            if ($x + $largeur > self::LARGEUR - self::MARGE) {
                break;
            }
            $this->pastille($image, $x, $y, $largeur, 46, $violet);
            imagettftext($image, 22, 0, $x + 18, $y + 32, $blanc, $normal, $techno);
            $x += $largeur + 14;
        }

        imagettftext($image, 22, 0, self::MARGE, self::HAUTEUR - 40, $cyan, $gras, 'nicolascataluna.fr');

        ob_start();
        imagepng($image, null, 6);

        return ob_get_clean();
    }

    /** Fond sombre en dégradé, halo violet à droite, étoiles placées d'après $graine (même image à chaque fois) */
    private function ciel(\GdImage $image, int $graine): void
    {
        for ($y = 0; $y < self::HAUTEUR; ++$y) {
            $t = $y / self::HAUTEUR;
            imageline($image, 0, $y, self::LARGEUR, $y, imagecolorallocate($image, (int) (10 + 16 * $t), (int) (10 + 6 * $t), (int) (25 + 40 * $t)));
        }
        for ($r = 420; $r > 0; $r -= 6) {
            $alpha = (int) (127 - 40 * (1 - $r / 420) ** 2);
            imagefilledellipse($image, 1040, 150, $r * 2, $r * 2, imagecolorallocatealpha($image, 127, 90, 240, $alpha));
        }
        mt_srand($graine);
        for ($i = 0; $i < 140; ++$i) {
            $eclat = mt_rand(120, 255);
            imagesetpixel($image, mt_rand(0, self::LARGEUR - 1), mt_rand(0, self::HAUTEUR - 1), imagecolorallocate($image, $eclat, $eclat, 255));
        }
        mt_srand();
    }

    /** @return list<string> le texte coupé en lignes qui tiennent dans $largeur */
    private function lignes(string $texte, string $police, int $taille, int $largeur): array
    {
        $lignes = [''];
        foreach (preg_split('/\s+/', trim($texte)) as $mot) {
            $essai = trim(end($lignes).' '.$mot);
            $boite = imagettfbbox($taille, 0, $police, $essai);
            if ($boite[2] - $boite[0] <= $largeur || '' === end($lignes)) {
                $lignes[array_key_last($lignes)] = $essai;
            } else {
                $lignes[] = $mot;
            }
        }

        return $lignes;
    }

    private function pastille(\GdImage $image, int $x, int $y, int $largeur, int $hauteur, int $couleur): void
    {
        $r = intdiv($hauteur, 2);
        imagefilledrectangle($image, $x + $r, $y, $x + $largeur - $r, $y + $hauteur, $couleur);
        imagefilledellipse($image, $x + $r, $y + $r, $hauteur, $hauteur, $couleur);
        imagefilledellipse($image, $x + $largeur - $r, $y + $r, $hauteur, $hauteur, $couleur);
    }
}
