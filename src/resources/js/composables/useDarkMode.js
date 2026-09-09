import { ref, onMounted } from 'vue';

const isDark = ref(false);

export function useDarkMode() {
    const initDarkMode = () => {
        if (typeof window === 'undefined') return;

        const stored = localStorage.getItem('theme');
        if (stored) {
            isDark.value = stored === 'dark';
        } else {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        applyTheme();
    };

    const applyTheme = () => {
        if (typeof document === 'undefined') return;
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
        if (typeof window !== 'undefined') {
            localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
        }
        applyTheme();
    };

    onMounted(() => {
        initDarkMode();
    });

    return {
        isDark,
        toggleDarkMode,
        initDarkMode,
    };
}
