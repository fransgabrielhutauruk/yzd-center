/**
 * Deteksi Kualitas Gambar di Sisi Klien (Resolusi Minimal & Heuristik Laplacian Variance).
 * Setara dengan DocumentQualityService di backend untuk memberikan umpan balik langsung.
 */
export function inspectImageQuality(img, minWidth = 600, minHeight = 400, blurThreshold = 45.0) {
    if (!img) {
        return { isQualityWarning: false, warningMessage: null, blurScore: null };
    }

    const naturalWidth = img.naturalWidth || img.width || 0;
    const naturalHeight = img.naturalHeight || img.height || 0;

    // 1. Cek dimensi resolusi minimal (orientasi landscape atau portrait)
    const longSide = Math.max(naturalWidth, naturalHeight);
    const shortSide = Math.min(naturalWidth, naturalHeight);

    if (longSide < minWidth || shortSide < minHeight) {
        return {
            isQualityWarning: true,
            warningMessage: `Resolusi gambar terlalu rendah (${naturalWidth}x${naturalHeight} piksel). Disarankan minimal ${minWidth}x${minHeight} piksel agar teks dokumen terbaca jelas.`,
            blurScore: null,
            width: naturalWidth,
            height: naturalHeight,
        };
    }

    // 2. Cek estimasi ketajaman gambar menggunakan Laplacian Variance pada thumbnail 160x120
    try {
        const sampleW = 160;
        const sampleH = 120;
        const sampleCanvas = document.createElement('canvas');
        sampleCanvas.width = sampleW;
        sampleCanvas.height = sampleH;
        const ctx = sampleCanvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) {
            return { isQualityWarning: false, warningMessage: null, blurScore: null, width: naturalWidth, height: naturalHeight };
        }

        ctx.drawImage(img, 0, 0, sampleW, sampleH);
        const imgData = ctx.getImageData(0, 0, sampleW, sampleH);
        const data = imgData.data;

        // Konversi ke skala abu-abu (grayscale)
        const gray = new Float32Array(sampleW * sampleH);
        for (let i = 0, p = 0; i < data.length; i += 4, p++) {
            gray[p] = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        }

        // Terapkan matriks konvolusi Laplacian 3x3
        const lapValues = [];
        for (let y = 1; y < sampleH - 1; y++) {
            for (let x = 1; x < sampleW - 1; x++) {
                const idx = y * sampleW + x;
                const lap =
                    gray[idx - sampleW] +
                    gray[idx + sampleW] +
                    gray[idx - 1] +
                    gray[idx + 1] -
                    4 * gray[idx];
                lapValues.push(Math.min(255, Math.max(0, Math.abs(lap))));
            }
        }

        if (lapValues.length === 0) {
            return { isQualityWarning: false, warningMessage: null, blurScore: null, width: naturalWidth, height: naturalHeight };
        }

        let sum = 0;
        for (let i = 0; i < lapValues.length; i++) {
            sum += lapValues[i];
        }
        const mean = sum / lapValues.length;

        let varianceSum = 0;
        for (let i = 0; i < lapValues.length; i++) {
            const diff = lapValues[i] - mean;
            varianceSum += diff * diff;
        }
        const blurScore = varianceSum / lapValues.length;

        if (blurScore < blurThreshold) {
            return {
                isQualityWarning: true,
                warningMessage: 'Kualitas gambar terindikasi buram atau kurang fokus. Pastikan teks pada dokumen terbaca jelas.',
                blurScore,
                width: naturalWidth,
                height: naturalHeight,
            };
        }

        return {
            isQualityWarning: false,
            warningMessage: null,
            blurScore,
            width: naturalWidth,
            height: naturalHeight,
        };
    } catch (e) {
        return { isQualityWarning: false, warningMessage: null, blurScore: null, width: naturalWidth, height: naturalHeight };
    }
}

/**
 * Utilitas Kompresi Gambar di Sisi Klien (Client-Side HTML5 Canvas)
 * Mengompresi foto berukuran besar (hingga 10-20 MB) menjadi berkas ringan (< 1 MB)
 * beresolusi 2K tanpa mengurangi keterbacaan teks kartu identitas.
 */
export async function compressImageFile(file, maxWidth = 2048, maxHeight = 2048, quality = 0.85) {
    if (!file) return null;

    // Jika berkas adalah PDF, lewati kompresi canvas
    if (file.type === 'application/pdf') {
        return {
            file,
            originalSize: file.size,
            compressedSize: file.size,
            isCompressed: false,
            previewUrl: null,
            isQualityWarning: false,
            warningMessage: null,
            blurScore: null,
        };
    }

    // Jika berkas bukan gambar yang didukung
    if (!file.type.startsWith('image/')) {
        return {
            file,
            originalSize: file.size,
            compressedSize: file.size,
            isCompressed: false,
            previewUrl: null,
            isQualityWarning: false,
            warningMessage: null,
            blurScore: null,
        };
    }

    return new Promise((resolve) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            const img = new Image();

            img.onload = () => {
                const quality = inspectImageQuality(img);

                let width = img.width;
                let height = img.height;

                // Skala proporsional jika melebihi batas resolusi 2K
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            resolve({
                                file,
                                originalSize: file.size,
                                compressedSize: file.size,
                                isCompressed: false,
                                previewUrl: event.target.result,
                                isQualityWarning: quality.isQualityWarning,
                                warningMessage: quality.warningMessage,
                                blurScore: quality.blurScore,
                            });
                            return;
                        }

                        // Buat File baru dari Blob yang sudah dikompresi
                        const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                        const compressedFile = new File([blob], cleanName, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });

                        const previewUrl = URL.createObjectURL(blob);

                        resolve({
                            file: compressedFile,
                            originalSize: file.size,
                            compressedSize: compressedFile.size,
                            isCompressed: compressedFile.size < file.size,
                            previewUrl,
                            isQualityWarning: quality.isQualityWarning,
                            warningMessage: quality.warningMessage,
                            blurScore: quality.blurScore,
                        });
                    },
                    'image/jpeg',
                    quality
                );
            };

            img.onerror = () => {
                resolve({
                    file,
                    originalSize: file.size,
                    compressedSize: file.size,
                    isCompressed: false,
                    previewUrl: event.target.result,
                    isQualityWarning: false,
                    warningMessage: null,
                    blurScore: null,
                });
            };

            img.src = event.target.result;
        };

        reader.onerror = () => {
            resolve({
                file,
                originalSize: file.size,
                compressedSize: file.size,
                isCompressed: false,
                previewUrl: null,
                isQualityWarning: false,
                warningMessage: null,
                blurScore: null,
            });
        };

        reader.readAsDataURL(file);
    });
}

/**
 * Format ukuran byte menjadi format ramah pengguna (KB / MB).
 */
export function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}