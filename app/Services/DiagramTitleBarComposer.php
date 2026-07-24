<?php

namespace App\Services;

/**
 * Agrega la barra de título azul marino sobre el diagrama de flujo ya renderizado — igual que
 * el documento_ejemplo.docx trae "DIAGRAMA DE FLUJO — CÓDIGO NOMBRE (N pasos)" como banner fijo
 * encima del diagrama. Se compone aquí con GD (fuera del control de la IA y de Mermaid) por la
 * misma razón que el encabezado del documento se construye en PHP: ni el modelo ni el tema por
 * defecto de Mermaid pueden garantizar el mismo banner exacto en cada generación.
 */
class DiagramTitleBarComposer
{
    private const BAR_COLOR = [0x1F, 0x38, 0x64];
    private const TEXT_COLOR = [0xFF, 0xFF, 0xFF];
    private const BAR_HEIGHT = 46;

    /**
     * @return string  PNG resultante (con la barra ya compuesta). Si GD no está disponible, o el
     *                 PNG de entrada no es válido, devuelve $png sin modificar en vez de fallar.
     */
    public function addTitleBar(string $png, string $title): string
    {
        if (! extension_loaded('gd')) {
            return $png;
        }

        $diagram = @imagecreatefromstring($png);
        if ($diagram === false) {
            return $png;
        }

        $width = imagesx($diagram);
        $height = imagesy($diagram);

        $canvas = imagecreatetruecolor($width, $height + self::BAR_HEIGHT);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        $barColor = imagecolorallocate($canvas, ...self::BAR_COLOR);
        imagefilledrectangle($canvas, 0, 0, $width - 1, self::BAR_HEIGHT - 1, $barColor);

        imagecopy($canvas, $diagram, 0, self::BAR_HEIGHT, 0, 0, $width, $height);
        imagedestroy($diagram);

        $this->drawCenteredTitle($canvas, $title, $width);

        ob_start();
        imagepng($canvas);
        $result = ob_get_clean();
        imagedestroy($canvas);

        return $result !== false ? $result : $png;
    }

    private function drawCenteredTitle($canvas, string $title, int $width): void
    {
        $title = mb_strtoupper($title, 'UTF-8');
        $textColor = imagecolorallocate($canvas, ...self::TEXT_COLOR);
        $font = $this->findBoldFont();

        if ($font !== null) {
            $size = 13;
            // imagettfbbox no soporta bien texto muy largo centrado a un tamaño fijo — se reduce
            // el tamaño de fuente hasta que quepa en el ancho del diagrama, con margen a los lados.
            do {
                $box = imagettfbbox($size, 0, $font, $title);
                $textWidth = abs($box[2] - $box[0]);
                $size--;
            } while ($textWidth > $width - 40 && $size > 7);

            $box = imagettfbbox($size + 1, 0, $font, $title);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);
            $x = max(20, (int) (($width - $textWidth) / 2));
            $y = (int) ((self::BAR_HEIGHT + $textHeight) / 2);

            imagettftext($canvas, $size + 1, 0, $x, $y, $textColor, $font, $title);

            return;
        }

        // Sin fuente TTF disponible (ej. Linux sin fontconfig): fuente bitmap de GD como respaldo.
        $charWidth = imagefontwidth(5);
        $x = max(10, (int) (($width - strlen($title) * $charWidth) / 2));
        $y = (int) ((self::BAR_HEIGHT - imagefontheight(5)) / 2);
        imagestring($canvas, 5, $x, $y, $title, $textColor);
    }

    private function findBoldFont(): ?string
    {
        foreach ([
            'C:/Windows/Fonts/arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
