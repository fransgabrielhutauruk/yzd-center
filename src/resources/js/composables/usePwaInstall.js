import { ref, computed } from 'vue';

const deferredPrompt = ref(null);
const isStandalone = ref(false);
const isInstalled = ref(false);
const isIos = ref(false);
let isInitialized = false;

const checkStandalone = () => {
    if (typeof window === 'undefined') return false;

    const isMediaStandalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    const isWindowControlsOverlay = window.matchMedia && window.matchMedia('(display-mode: window-controls-overlay)').matches;
    const isNavStandalone = window.navigator && Boolean(window.navigator.standalone);

    return Boolean(isMediaStandalone || isWindowControlsOverlay || isNavStandalone);
};

const checkIos = () => {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') return false;

    const ua = window.navigator.userAgent || '';
    const isAppleMobile = /iPad|iPhone|iPod/.test(ua);
    const isMacTouch = window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1;

    return Boolean((isAppleMobile || isMacTouch) && !window.MSStream);
};

const handlePromptAvailable = (e) => {
    deferredPrompt.value = e.detail || e;
};

const handleAppInstalled = () => {
    deferredPrompt.value = null;
    isInstalled.value = true;
    isStandalone.value = true;
};

const initPwa = () => {
    if (typeof window === 'undefined' || isInitialized) return;
    isInitialized = true;

    isStandalone.value = checkStandalone();
    isIos.value = checkIos();

    // Ambil deferred prompt awal jika sudah ditangkap di app.blade.php
    if (window.__deferredPwaPrompt) {
        deferredPrompt.value = window.__deferredPwaPrompt;
    }

    // Dengarkan event custom dan native untuk beforeinstallprompt
    window.addEventListener('pwa-prompt-available', handlePromptAvailable);
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.__deferredPwaPrompt = e;
        deferredPrompt.value = e;
    });

    // Dengarkan event appinstalled
    window.addEventListener('pwa-installed', handleAppInstalled);
    window.addEventListener('appinstalled', handleAppInstalled);

    // Dengarkan perubahan display-mode jika pengguna menginstall di tab lain
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(display-mode: standalone)');
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', (e) => {
                isStandalone.value = Boolean(e.matches);
            });
        }
    }
};

if (typeof window !== 'undefined') {
    initPwa();
}

export function usePwaInstall() {
    initPwa();

    const canShowInstallButton = computed(() => {
        if (isStandalone.value || isInstalled.value) {
            return false;
        }

        // Tampilkan jika event prompt tersedia atau berada di Safari iOS (panduan manual)
        return Boolean(deferredPrompt.value) || isIos.value;
    });

    const triggerInstall = async () => {
        if (deferredPrompt.value) {
            const promptEvent = deferredPrompt.value;
            // Bersihkan referensi agar tidak dipanggil dua kali
            deferredPrompt.value = null;
            if (typeof window !== 'undefined') {
                window.__deferredPwaPrompt = null;
            }

            try {
                await promptEvent.prompt();
                const choiceResult = await promptEvent.userChoice;

                if (choiceResult && choiceResult.outcome === 'accepted') {
                    isInstalled.value = true;
                    isStandalone.value = true;
                    return { outcome: 'accepted' };
                }

                return { outcome: choiceResult ? choiceResult.outcome : 'dismissed' };
            } catch (err) {
                console.error('PWA install prompt error:', err);
                return { outcome: 'error', error: err };
            }
        }

        if (isIos.value) {
            return { outcome: 'ios_guide' };
        }

        return { outcome: 'unavailable' };
    };

    return {
        isStandalone,
        isInstalled,
        isIos,
        deferredPrompt,
        canShowInstallButton,
        triggerInstall,
    };
}
