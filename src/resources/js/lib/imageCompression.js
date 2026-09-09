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
        };
    }

    return new Promise((resolve) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            const img = new Image();

            img.onload = () => {
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