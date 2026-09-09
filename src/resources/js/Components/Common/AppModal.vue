<script setup>
import { watch, onMounted, onUnmounted, computed } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl'].includes(value),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show && props.closeable) {
        close();
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (typeof document !== 'undefined') {
            if (isOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const maxWidthClass = computed(() => {
    switch (props.maxWidth) {
        case 'sm':
            return 'max-w-sm';
        case 'md':
            return 'max-w-md';
        case 'lg':
            return 'max-w-lg';
        case 'xl':
            return 'max-w-xl';
        case '2xl':
            return 'max-w-2xl';
        case '3xl':
            return 'max-w-3xl';
        case '4xl':
            return 'max-w-4xl';
        default:
            return 'max-w-md';
    }
});
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center bg-black/60 backdrop-blur-xs"
                @click.self="close"
            >
                <transition
                    enter-active-class="transition ease-out duration-200 transform"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition ease-in duration-150 transform"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="show"
                        :class="[
                            'w-full bg-card text-card-foreground rounded-2xl border border-border shadow-2xl overflow-hidden transition-all my-8',
                            maxWidthClass
                        ]"
                    >
                        <!-- Header -->
                        <slot name="header">
                            <div v-if="title || closeable" class="px-6 py-4 border-b border-border flex items-center justify-between">
                                <h3 class="font-bold text-base text-foreground truncate">
                                    {{ title }}
                                </h3>
                                <button
                                    v-if="closeable"
                                    type="button"
                                    @click="close"
                                    class="p-1 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                                    aria-label="Tutup"
                                >
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                        </slot>

                        <!-- Body -->
                        <div class="p-6">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="px-6 py-3.5 bg-muted/40 border-t border-border flex items-center justify-end gap-3">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

