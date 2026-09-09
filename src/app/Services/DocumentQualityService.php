<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class DocumentQualityService
{
    /**
     * Dimensi minimal foto dokumen (KTP).
     */
    public const MIN_WIDTH = 600;

    public const MIN_HEIGHT = 400;

    /**
     * Ambang batas varians Laplacian untuk deteksi foto buram (blur threshold).
     * Nilai di bawah ini mengindikasikan foto kemungkinan buram/out-of-focus.
     */
    public const BLUR_THRESHOLD = 45.0;

    /**
     * Analisis kualitas berkas dokumen yang diunggah.
     *
     * @return array{is_quality_warning: bool, warning_message: ?string, width: ?int, height: ?int}
     */
    public function inspect(UploadedFile $file): array
    {
        $mime = $file->getMimeType();

        // Dokumen PDF dilewati dari pengecekan resolusi & blur gambar
        if ($mime === 'application/pdf') {
            return [
                'is_quality_warning' => false,
                'warning_message'    => null,
                'width'              => null,
                'height'             => null,
            ];
        }

        // Pastikan ekstensi GD aktif
        if (!extension_loaded('gd')) {
            return [
                'is_quality_warning' => false,
                'warning_message'    => null,
                'width'              => null,
                'height'             => null,
            ];
        }

        $imagePath = $file->getRealPath();
        $imageInfo = @getimagesize($imagePath);

        if (!$imageInfo) {
            return [
                'is_quality_warning' => true,
                'warning_message'    => 'Berkas gambar tidak dapat dibaca atau rusak.',
                'width'              => null,
                'height'             => null,
            ];
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // 1. Cek dimensi resolusi minimal
        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            return [
                'is_quality_warning' => true,
                'warning_message'    => "Resolusi gambar terlalu rendah ({$width}x{$height} piksel). Minimal yang disarankan adalah 600x400 piksel.",
                'width'              => $width,
                'height'             => $height,
            ];
        }

        // 2. Cek estimasi ketajaman gambar (Laplacian Variance Heuristic)
        $blurScore = $this->calculateLaplacianVariance($imagePath, $imageInfo[2]);

        if ($blurScore !== null && $blurScore < self::BLUR_THRESHOLD) {
            return [
                'is_quality_warning' => true,
                'warning_message'    => 'Kualitas gambar terindikasi buram atau kurang fokus. Pastikan teks pada KTP terbaca jelas.',
                'width'              => $width,
                'height'             => $height,
            ];
        }

        return [
            'is_quality_warning' => false,
            'warning_message'    => null,
            'width'              => $width,
            'height'             => $height,
        ];
    }

    /**
     * Hitung varians gradien tepi (Laplacian filter) pada gambar berukuran sampel.
     */
    private function calculateLaplacianVariance(string $imagePath, int $imageType): ?float
    {
        try {
            $sourceImage = match ($imageType) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($imagePath),
                IMAGETYPE_PNG  => @imagecreatefrompng($imagePath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($imagePath),
                default        => null,
            };

            if (!$sourceImage) {
                return null;
            }

            // Downsample ke thumbnail kecil (160x120) agar eksekusi sangat cepat (< 15ms)
            $sampleWidth = 160;
            $sampleHeight = 120;
            $sampleImage = imagecreatetruecolor($sampleWidth, $sampleHeight);
            imagecopyresampled(
                $sampleImage,
                $sourceImage,
                0, 0, 0, 0,
                $sampleWidth,
                $sampleHeight,
                imagesx($sourceImage),
                imagesy($sourceImage)
            );
            imagedestroy($sourceImage);

            // Konversi ke skala abu-abu (grayscale)
            imagefilter($sampleImage, IMG_FILTER_GRAYSCALE);

            // Terapkan matriks konvolusi Laplacian 3x3
            $laplacianMatrix = [
                [0,  1, 0],
                [1, -4, 1],
                [0,  1, 0],
            ];

            imageconvolution($sampleImage, $laplacianMatrix, 1, 0);

            // Hitung varians intensitas piksel
            $values = [];
            for ($x = 1; $x < $sampleWidth - 1; $x++) {
                for ($y = 1; $y < $sampleHeight - 1; $y++) {
                    $rgb = imagecolorat($sampleImage, $x, $y);
                    $values[] = $rgb & 0xFF;
                }
            }
            imagedestroy($sampleImage);

            $count = count($values);
            if ($count === 0) {
                return null;
            }

            $mean = array_sum($values) / $count;
            $variance = 0.0;
            foreach ($values as $val) {
                $variance += ($val - $mean) ** 2;
            }

            return $variance / $count;
        } catch (\Throwable) {
            return null;
        }
    }
}
