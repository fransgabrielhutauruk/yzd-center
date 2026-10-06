<script setup>
import { ref, computed } from 'vue';
import { nodeViewProps, NodeViewWrapper } from '@tiptap/vue-3';
import {
    AlignLeft,
    AlignCenter,
    AlignRight,
    Trash2,
    Maximize2,
} from 'lucide-vue-next';

const props = defineProps(nodeViewProps);

const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartWidthPx = ref(0);
const imgRef = ref(null);

const currentWidth = computed(() => props.node.attrs.width || '100%');
const currentAlignment = computed(() => props.node.attrs.alignment || 'center');

const alignmentContainerClass = computed(() => {
    if (currentAlignment.value === 'left') return 'text-left';
    if (currentAlignment.value === 'right') return 'text-right';
    return 'text-center';
});

const setPresetWidth = (widthPercent) => {
    props.updateAttributes({ width: widthPercent });
};

const setAlignment = (align) => {
    props.updateAttributes({ alignment: align });
};

const deleteImage = () => {
    props.deleteNode();
};

// Handle interaktif resize via drag sudut gambar
const startResize = (event, direction) => {
    event.preventDefault();
    event.stopPropagation();

    isDragging.value = true;
    dragStartX.value = event.clientX;

    const imgEl = imgRef.value;
    if (!imgEl) return;

    const rect = imgEl.getBoundingClientRect();
    dragStartWidthPx.value = rect.width;

    const onMouseMove = (moveEvent) => {
        if (!isDragging.value) return;

        const parentEl = imgEl.closest('.ProseMirror') || imgEl.parentElement;
        const parentWidth = parentEl?.clientWidth || 700;

        let diffX = moveEvent.clientX - dragStartX.value;
        if (direction === 'sw') {
            diffX = -diffX;
        }

        const newWidthPx = dragStartWidthPx.value + diffX;
        let newPercent = Math.round((newWidthPx / parentWidth) * 100);
        newPercent = Math.max(15, Math.min(100, newPercent));

        props.updateAttributes({ width: `${newPercent}%` });
    };

    const onMouseUp = () => {
        isDragging.value = false;
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};
</script>

<template>
    <NodeViewWrapper :class="['my-5 transition-all select-none', alignmentContainerClass]">
        <div
            class="relative inline-block group"
            :style="{ width: currentWidth, maxWidth: '100%' }"
        >
            <!-- Gambar Utama -->
            <img
                ref="imgRef"
                :src="node.attrs.src"
                :alt="node.attrs.alt || ''"
                :class="[
                    'w-full h-auto rounded-xl border border-border shadow-xs block transition-all cursor-pointer',
                    selected
                        ? 'ring-3 ring-primary-500 ring-offset-2 ring-offset-background shadow-md'
                        : 'hover:ring-2 hover:ring-primary-500/50',
                ]"
            />

            <!-- Floating Quick Toolbar saat gambar terpilih (selected) -->
            <div
                v-if="selected"
                class="absolute -top-12 left-1/2 -translate-x-1/2 bg-popover text-popover-foreground border border-border shadow-lg rounded-xl px-2 py-1 flex items-center gap-1 z-30 animate-in fade-in zoom-in-95 duration-150 backdrop-blur-md"
            >
                <!-- Preset Ukuran -->
                <button
                    type="button"
                    @click.stop="setPresetWidth('25%')"
                    :class="[
                        'px-2 py-1 text-xs rounded-md font-semibold transition-colors cursor-pointer',
                        currentWidth === '25%' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Ukuran Kecil (25%)"
                >
                    25%
                </button>
                <button
                    type="button"
                    @click.stop="setPresetWidth('50%')"
                    :class="[
                        'px-2 py-1 text-xs rounded-md font-semibold transition-colors cursor-pointer',
                        currentWidth === '50%' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Ukuran Sedang (50%)"
                >
                    50%
                </button>
                <button
                    type="button"
                    @click.stop="setPresetWidth('75%')"
                    :class="[
                        'px-2 py-1 text-xs rounded-md font-semibold transition-colors cursor-pointer',
                        currentWidth === '75%' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Ukuran Besar (75%)"
                >
                    75%
                </button>
                <button
                    type="button"
                    @click.stop="setPresetWidth('100%')"
                    :class="[
                        'px-2 py-1 text-xs rounded-md font-semibold transition-colors cursor-pointer',
                        currentWidth === '100%' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Lebar Penuh (100%)"
                >
                    100%
                </button>

                <span class="h-4 w-px bg-border mx-0.5" />

                <!-- Alignment Posisi -->
                <button
                    type="button"
                    @click.stop="setAlignment('left')"
                    :class="[
                        'p-1 text-xs rounded-md transition-colors cursor-pointer',
                        currentAlignment === 'left' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Rata Kiri"
                >
                    <AlignLeft class="w-3.5 h-3.5" />
                </button>
                <button
                    type="button"
                    @click.stop="setAlignment('center')"
                    :class="[
                        'p-1 text-xs rounded-md transition-colors cursor-pointer',
                        currentAlignment === 'center' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Rata Tengah"
                >
                    <AlignCenter class="w-3.5 h-3.5" />
                </button>
                <button
                    type="button"
                    @click.stop="setAlignment('right')"
                    :class="[
                        'p-1 text-xs rounded-md transition-colors cursor-pointer',
                        currentAlignment === 'right' ? 'bg-primary-600 text-white' : 'hover:bg-muted text-muted-foreground hover:text-foreground',
                    ]"
                    title="Rata Kanan"
                >
                    <AlignRight class="w-3.5 h-3.5" />
                </button>

                <span class="h-4 w-px bg-border mx-0.5" />

                <!-- Tombol Hapus Gambar -->
                <button
                    type="button"
                    @click.stop="deleteImage"
                    class="p-1 text-xs rounded-md text-destructive hover:bg-destructive/10 transition-colors cursor-pointer"
                    title="Hapus Gambar"
                >
                    <Trash2 class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- Handle Resize Sudut (Hanya muncul saat gambar aktif / selected) -->
            <template v-if="selected">
                <!-- Sudut Kanan Bawah -->
                <div
                    @mousedown="startResize($event, 'se')"
                    class="absolute -right-2 -bottom-2 w-4 h-4 bg-primary-600 border-2 border-white dark:border-slate-900 rounded-full cursor-se-resize shadow-md hover:scale-125 transition-transform z-20"
                    title="Tarik untuk mengubah ukuran gambar"
                />
                <!-- Sudut Kiri Bawah -->
                <div
                    @mousedown="startResize($event, 'sw')"
                    class="absolute -left-2 -bottom-2 w-4 h-4 bg-primary-600 border-2 border-white dark:border-slate-900 rounded-full cursor-sw-resize shadow-md hover:scale-125 transition-transform z-20"
                    title="Tarik untuk mengubah ukuran gambar"
                />

                <!-- Label Ukuran Aktif saat Drag / Hover -->
                <div class="absolute bottom-2 right-2 bg-black/75 text-white text-[10px] font-mono font-bold px-1.5 py-0.5 rounded shadow pointer-events-none">
                    {{ currentWidth }}
                </div>
            </template>
        </div>
    </NodeViewWrapper>
</template>
