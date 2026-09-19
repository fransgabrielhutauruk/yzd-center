<script setup>
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import Bold from '@tiptap/extension-bold';
import Italic from '@tiptap/extension-italic';
import Underline from '@tiptap/extension-underline';
import Heading from '@tiptap/extension-heading';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
import ListItem from '@tiptap/extension-list-item';
import Blockquote from '@tiptap/extension-blockquote';
import History from '@tiptap/extension-history';
import Placeholder from '@tiptap/extension-placeholder';
import {
    Bold as BoldIcon,
    Italic as ItalicIcon,
    Underline as UnderlineIcon,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    TextQuote,
    Undo2,
    Redo2,
    Eraser,
    Pilcrow,
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Tuliskan pasal-pasal perjanjian di sini...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

// Seluruh gaya konten via utility Tailwind + plugin typography (tanpa <style>)
const contentClass = [
    'prose prose-sm max-w-none',
    'min-h-[320px] max-h-[520px] overflow-y-auto px-5 py-4',
    'focus:outline-none',
    '[--tw-prose-body:var(--color-foreground)]',
    '[--tw-prose-headings:var(--color-foreground)]',
    '[--tw-prose-bold:var(--color-foreground)]',
    '[--tw-prose-counters:var(--color-muted-foreground)]',
    '[--tw-prose-bullets:var(--color-muted-foreground)]',
    '[--tw-prose-quotes:var(--color-muted-foreground)]',
    '[--tw-prose-quote-borders:var(--color-primary-500)]',
    '[&_p]:my-1.5 [&_ul]:my-1.5 [&_ol]:my-1.5 [&_li]:my-1',
    '[&_h2]:mt-5 [&_h2]:mb-1.5 [&_h3]:mt-4 [&_h3]:mb-1 [&_blockquote]:my-2',
    '[&_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)]',
    '[&_p.is-editor-empty:first-child::before]:float-left',
    '[&_p.is-editor-empty:first-child::before]:h-0',
    '[&_p.is-editor-empty:first-child::before]:pointer-events-none',
    '[&_p.is-editor-empty:first-child::before]:text-muted-foreground',
].join(' ');

const editor = useEditor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
        Document,
        Paragraph,
        Text,
        Bold,
        Italic,
        Underline,
        Heading.configure({ levels: [2, 3] }),
        BulletList,
        OrderedList,
        ListItem,
        Blockquote,
        History,
        Placeholder.configure({
            placeholder: ({ editor }) => (editor.getText().trim() === '' ? props.placeholder : ''),
        }),
    ],
    editorProps: {
        attributes: {
            class: contentClass,
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

// Sinkronisasi saat nilai luar berubah (mis. ganti tab dokumen)
watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.getHTML();
        if ((value || '') !== current) {
            editor.value.commands.setContent(value || '', { emitUpdate: false });
        }
    }
);

watch(
    () => props.disabled,
    (value) => {
        editor.value?.setEditable(!value);
    }
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const buttonClass = (active) => [
    'flex h-8 w-8 items-center justify-center rounded-lg transition-colors',
    active
        ? 'bg-primary-600 text-white shadow-xs'
        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
];
</script>

<template>
    <div
        class="bg-background border-input focus-within:ring-primary-500 overflow-hidden rounded-xl border transition-all focus-within:ring-2"
    >
        <!-- Toolbar dasar -->
        <div
            v-if="editor"
            class="border-input bg-muted/40 flex flex-wrap items-center gap-1 border-b p-2"
        >
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :disabled="!editor.can().chain().focus().toggleBold().run()"
                :class="buttonClass(editor.isActive('bold'))"
                title="Tebal"
            >
                <BoldIcon class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :disabled="!editor.can().chain().focus().toggleItalic().run()"
                :class="buttonClass(editor.isActive('italic'))"
                title="Miring"
            >
                <ItalicIcon class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="buttonClass(editor.isActive('underline'))"
                title="Garis bawah"
            >
                <UnderlineIcon class="h-4 w-4" />
            </button>

            <span class="bg-border mx-1 h-5 w-px" />

            <button
                type="button"
                @click="editor.chain().focus().setParagraph().run()"
                :class="buttonClass(editor.isActive('paragraph'))"
                title="Teks biasa"
            >
                <Pilcrow class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="buttonClass(editor.isActive('heading', { level: 2 }))"
                title="Judul bagian"
            >
                <Heading2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="buttonClass(editor.isActive('heading', { level: 3 }))"
                title="Sub judul"
            >
                <Heading3 class="h-4 w-4" />
            </button>

            <span class="bg-border mx-1 h-5 w-px" />

            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="buttonClass(editor.isActive('bulletList'))"
                title="Daftar poin"
            >
                <List class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="buttonClass(editor.isActive('orderedList'))"
                title="Daftar bernomor"
            >
                <ListOrdered class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="buttonClass(editor.isActive('blockquote'))"
                title="Kutipan pasal"
            >
                <TextQuote class="h-4 w-4" />
            </button>

            <span class="bg-border mx-1 h-5 w-px" />

            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().chain().focus().undo().run()"
                class="text-muted-foreground hover:bg-muted hover:text-foreground flex h-8 w-8 items-center justify-center rounded-lg transition-colors disabled:opacity-40"
                title="Urungkan"
            >
                <Undo2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().chain().focus().redo().run()"
                class="text-muted-foreground hover:bg-muted hover:text-foreground flex h-8 w-8 items-center justify-center rounded-lg transition-colors disabled:opacity-40"
                title="Ulangi"
            >
                <Redo2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().unsetAllMarks().clearNodes().run()"
                class="text-muted-foreground hover:bg-muted hover:text-foreground flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                title="Bersihkan format"
            >
                <Eraser class="h-4 w-4" />
            </button>
        </div>

        <!-- Area editor -->
        <EditorContent :editor="editor" />
    </div>
</template>
