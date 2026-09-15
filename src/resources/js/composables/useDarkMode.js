import { ref, onMounted } from 'vue';

const isDark = ref(false);

const applyTheme = () => {
    if (typeof document === 'undefined') return;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const initDarkMode = () => {
    if (typeof window === 'undefined') return;

    const stored = localStorage.getItem('theme');
    isDark.value = stored === 'dark';
    applyTheme();
};

if (typeof window !== 'undefined') {
    initDarkMode();
}

export function useDarkMode() {
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
