<script setup>
import { ref, watch, onBeforeUnmount, computed } from 'vue';
import { useEditor, EditorContent, VueNodeViewRenderer } from '@tiptap/vue-3';
import { Node, mergeAttributes } from '@tiptap/core';
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
import CodeBlock from '@tiptap/extension-code-block';
import HorizontalRule from '@tiptap/extension-horizontal-rule';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import History from '@tiptap/extension-history';
import Placeholder from '@tiptap/extension-placeholder';
import AppEditorImageNode from '@/Components/Common/AppEditorImageNode.vue';
import {
    Bold as BoldIcon,
    Italic as ItalicIcon,
    Underline as UnderlineIcon,
    Heading1,
    Heading2,
    Heading3,
    Pilcrow,
    List,
    ListOrdered,
    TextQuote,
    Code2,
    Minus,
    Link2,
    Unlink,
    Image as ImageIcon,
    Video as VideoIcon,
    Undo2,
    Redo2,
    Eraser,
    UploadCloud,
    Loader2,
    X,
    Check,
    Info,
} from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';

// -----------------------------------------------------------------------------
// Tiptap Extension: Gambar Interaktif dengan Ukuran & Penyelarasan Posisi
// -----------------------------------------------------------------------------
const ResizableImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: '100%',
                parseHTML: (element) => element.style.width || element.getAttribute('width') || '100%',
                renderHTML: (attributes) => ({
                    width: attributes.width,
                    style: `width: ${attributes.width}; max-width: 100%; height: auto;`,
                }),
            },
            alignment: {
                default: 'center',
                parseHTML: (element) => element.getAttribute('data-alignment') || 'center',
                renderHTML: (attributes) => {
                    let alignClass = 'mx-auto block';
                    if (attributes.alignment === 'left') alignClass = 'mr-auto ml-0 block';
                    if (attributes.alignment === 'right') alignClass = 'ml-auto mr-0 block';

                    return {
                        'data-alignment': attributes.alignment,
                        class: `rounded-xl border border-border shadow-xs my-4 ${alignClass}`,
                    };
                },
            },
        };
    },

    addNodeView() {
        return VueNodeViewRenderer(AppEditorImageNode);
    },
});

// -----------------------------------------------------------------------------
// Tiptap Extension: Video Embed Universal (YouTube, Google Drive, Vimeo, Loom)
// -----------------------------------------------------------------------------
const VideoEmbed = Node.create({
    name: 'videoEmbed',
    group: 'block',
    selectable: true,
    draggable: true,
    atom: true,

    addAttributes() {
        return {
            src: {
                default: null,
            },
            provider: {
                default: 'video',
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-video-embed]',
                getAttrs: (element) => ({
                    src: element.getAttribute('data-video-src') || element.querySelector('iframe')?.getAttribute('src'),
                    provider: element.getAttribute('data-provider') || 'video',
                }),
            },
            {
                tag: 'iframe[src]',
                getAttrs: (element) => ({
                    src: element.getAttribute('src'),
                    provider: 'video',
                }),
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            {
                class: 'relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950',
                'data-video-embed': '',
                'data-video-src': HTMLAttributes.src,
                'data-provider': HTMLAttributes.provider,
            },
            [
                'iframe',
                mergeAttributes(HTMLAttributes, {
                    class: 'absolute inset-0 w-full h-full border-0',
                    allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
                    allowfullscreen: 'true',
                }),
            ],
        ];
    },

    addCommands() {
        return {
            setVideoEmbed: (options) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: options,
                });
            },
        };
    },
});

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Tuliskan panduan artikel lengkap, langkah-langkah, dan penjelasan detail di sini...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    uploadUrl: {
        type: String,
        default: '/admin/education/help/upload-media',
    },
    minHeight: {
        type: String,
        default: 'min-h-[420px]',
    },
});

const emit = defineEmits(['update:modelValue']);

// Styling konten di dalam area editor Tiptap (Tailwind typography)
const contentClass = computed(() => [
    'prose prose-sm sm:prose-base max-w-none dark:prose-invert',
    props.minHeight,
    'max-h-[700px] overflow-y-auto px-5 py-4 focus:outline-none',
    '[--tw-prose-body:var(--color-foreground)]',
    '[--tw-prose-headings:var(--color-foreground)]',
    '[--tw-prose-bold:var(--color-foreground)]',
    '[--tw-prose-counters:var(--color-muted-foreground)]',
    '[--tw-prose-bullets:var(--color-muted-foreground)]',
    '[--tw-prose-quotes:var(--color-muted-foreground)]',
    '[--tw-prose-quote-borders:var(--color-primary-500)]',
    '[--tw-prose-code:var(--color-foreground)]',
    '[--tw-prose-pre-bg:var(--color-muted)]',
    '[--tw-prose-pre-code:var(--color-foreground)]',
    '[&_p]:my-2 [&_ul]:my-2 [&_ol]:my-2 [&_li]:my-1',
    '[&_h1]:text-2xl [&_h1]:font-extrabold [&_h1]:mt-6 [&_h1]:mb-3',
    '[&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-5 [&_h2]:mb-2.5',
    '[&_h3]:text-base [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-2',
    '[&_blockquote]:border-l-4 [&_blockquote]:border-primary-500 [&_blockquote]:bg-muted/40 [&_blockquote]:py-1.5 [&_blockquote]:px-4 [&_blockquote]:rounded-r-lg [&_blockquote]:my-3',
    '[&_code]:bg-muted [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded [&_code]:font-mono [&_code]:text-xs',
    '[&_pre]:bg-muted/80 [&_pre]:p-4 [&_pre]:rounded-xl [&_pre]:border [&_pre]:border-border [&_pre]:overflow-x-auto',
    '[&_hr]:my-6 [&_hr]:border-border',
    '[&_img]:rounded-xl [&_img]:border [&_img]:border-border [&_img]:shadow-xs [&_img]:max-w-full [&_img]:my-4',
    '[&_iframe]:rounded-xl [&_iframe]:w-full [&_iframe]:aspect-video [&_iframe]:my-4 [&_iframe]:border [&_iframe]:border-border',
    '[&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a]:underline [&_a]:font-medium hover:[&_a]:text-primary-700',
    '[&_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)]',
    '[&_p.is-editor-empty:first-child::before]:float-left',
    '[&_p.is-editor-empty:first-child::before]:h-0',
    '[&_p.is-editor-empty:first-child::before]:pointer-events-none',
    '[&_p.is-editor-empty:first-child::before]:text-muted-foreground/60',
].join(' '));

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
        Heading.configure({ levels: [1, 2, 3] }),
        BulletList,
        OrderedList,
        ListItem,
        Blockquote,
        CodeBlock,
        HorizontalRule,
        Link.configure({
            openOnClick: false,
            autolink: true,
            defaultProtocol: 'https',
            HTMLAttributes: {
                target: '_blank',
                rel: 'noopener noreferrer nofollow',
            },
        }),
        ResizableImage.configure({
            inline: false,
            allowBase64: true,
        }),
        VideoEmbed,
        History,
        Placeholder.configure({
            placeholder: ({ editor }) => (editor.getText().trim() === '' ? props.placeholder : ''),
        }),
    ],
    editorProps: {
        attributes: {
            class: contentClass.value,
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

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

// Hitung statistik teks
const wordCount = computed(() => {
    if (!editor.value) return 0;
    const text = editor.value.getText().trim();
    if (!text) return 0;
    return text.split(/\s+/).filter(Boolean).length;
});

const characterCount = computed(() => {
    if (!editor.value) return 0;
    return editor.value.getText().length;
});

const readingTime = computed(() => {
    const minutes = Math.ceil(wordCount.value / 200);
    return minutes < 1 ? 1 : minutes;
});

const buttonClass = (active) => [
    'flex h-8 w-8 items-center justify-center rounded-lg transition-colors cursor-pointer',
    active
        ? 'bg-primary-600 text-white shadow-2xs font-bold'
        : 'text-muted-foreground hover:bg-muted/80 hover:text-foreground',
];

// -----------------------------------------------------------------------------
// Dialog & Helper: Link
// -----------------------------------------------------------------------------
const showLinkModal = ref(false);
const linkUrl = ref('');
const linkText = ref('');

const openLinkModal = () => {
    if (!editor.value) return;
    const previousUrl = editor.value.getAttributes('link').href || '';
    const { from, to } = editor.value.state.selection;
    const selectedText = editor.value.state.doc.textBetween(from, to, ' ');

    linkUrl.value = previousUrl;
    linkText.value = selectedText;
    showLinkModal.value = true;
};

const applyLink = () => {
    if (!editor.value) return;

    if (!linkUrl.value.trim()) {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        showLinkModal.value = false;
        return;
    }

    let url = linkUrl.value.trim();
    if (!/^https?:\/\//i.test(url) && !url.startsWith('/')) {
        url = 'https://' + url;
    }

    if (linkText.value && editor.value.state.selection.empty) {
        editor.value
            .chain()
            .focus()
            .insertContent(`<a href="${url}" target="_blank" rel="noopener noreferrer">${linkText.value}</a>`)
            .run();
    } else {
        editor.value
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: url })
            .run();
    }

    showLinkModal.value = false;
};

const removeLink = () => {
    editor.value?.chain().focus().unsetLink().run();
    showLinkModal.value = false;
};

// -----------------------------------------------------------------------------
// Dialog & Helper: Gambar (Image Upload & External URL)
// -----------------------------------------------------------------------------
const showImageModal = ref(false);
const imageTab = ref('upload'); // 'upload' | 'url'
const imageUrl = ref('');
const imageAlt = ref('');
const isUploadingImage = ref(false);
const uploadError = ref('');
const fileInputRef = ref(null);

const openImageModal = () => {
    imageUrl.value = '';
    imageAlt.value = '';
    uploadError.value = '';
    imageTab.value = 'upload';
    showImageModal.value = true;
};

const handleFileUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Hanya berkas gambar (JPEG, PNG, WEBP, GIF) yang diperbolehkan.';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        uploadError.value = 'Ukuran berkas gambar maksimal 2MB.';
        return;
    }

    isUploadingImage.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Gagal mengunggah gambar.');
        }

        const data = await response.json();
        if (data.url) {
            editor.value
                ?.chain()
                .focus()
                .setImage({ src: data.url, alt: imageAlt.value || file.name })
                .run();
            showImageModal.value = false;
        }
    } catch (err) {
        uploadError.value = err.message || 'Terjadi kesalahan saat mengunggah gambar.';
    } finally {
        isUploadingImage.value = false;
        if (fileInputRef.value) fileInputRef.value.value = '';
    }
};

const applyImageUrl = () => {
    if (!imageUrl.value.trim() || !editor.value) return;

    editor.value
        .chain()
        .focus()
        .setImage({ src: imageUrl.value.trim(), alt: imageAlt.value.trim() || 'Gambar Panduan' })
        .run();

    showImageModal.value = false;
};

// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------
// Dialog & Helper: Video Embed Universal (YouTube, Google Drive, Vimeo, Loom)
// -----------------------------------------------------------------------------
const showVideoModal = ref(false);
const videoInputUrl = ref('');
const videoError = ref('');

const parseVideoUrl = (rawUrl) => {
    if (!rawUrl || typeof rawUrl !== 'string') return null;
    const url = rawUrl.trim();

    // 1. Google Drive (Format share / view diubah otomatis ke format /preview embed)
    const gDriveMatch1 = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/i);
    const gDriveMatch2 = url.match(/[?&]id=([a-zA-Z0-9_-]+)/i);
    const gDriveId = gDriveMatch1?.[1] || gDriveMatch2?.[1];
    if (gDriveId) {
        return {
            provider: 'Google Drive',
            embedUrl: `https://drive.google.com/file/d/${gDriveId}/preview`,
            isGoogleDrive: true,
        };
    }

    // 2. YouTube
    const ytMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
    if (ytMatch?.[1]) {
        return {
            provider: 'YouTube',
            embedUrl: `https://www.youtube.com/embed/${ytMatch[1]}`,
            isGoogleDrive: false,
        };
    }

    // 3. Vimeo
    const vimeoMatch = url.match(/(?:vimeo\.com\/)(\d+)/i);
    if (vimeoMatch?.[1]) {
        return {
            provider: 'Vimeo',
            embedUrl: `https://player.vimeo.com/video/${vimeoMatch[1]}`,
            isGoogleDrive: false,
        };
    }

    // 4. Loom
    const loomMatch = url.match(/(?:loom\.com\/share\/)([a-zA-Z0-9]+)/i);
    if (loomMatch?.[1]) {
        return {
            provider: 'Loom',
            embedUrl: `https://www.loom.com/embed/${loomMatch[1]}`,
            isGoogleDrive: false,
        };
    }

    // 5. URL Embed / Web Video Umum
    if (/^https?:\/\//i.test(url)) {
        return {
            provider: 'Web Video',
            embedUrl: url,
            isGoogleDrive: false,
        };
    }

    return null;
};

const detectedVideo = computed(() => parseVideoUrl(videoInputUrl.value));

const openVideoModal = () => {
    videoInputUrl.value = '';
    videoError.value = '';
    showVideoModal.value = true;
};

const applyVideo = () => {
    if (!videoInputUrl.value.trim() || !editor.value) return;

    const video = detectedVideo.value;
    if (!video || !video.embedUrl) {
        videoError.value = 'Format URL video tidak valid. Masukkan link YouTube, Google Drive, Vimeo, atau Loom yang valid.';
        return;
    }

    const embedHtml = `<div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950" data-video-embed="" data-video-src="${video.embedUrl}" data-provider="${video.provider}"><iframe src="${video.embedUrl}" class="absolute inset-0 w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="true"></iframe></div><p></p>`;

    editor.value
        .chain()
        .focus()
        .insertContent(embedHtml)
        .run();

    showVideoModal.value = false;
};
</script>

<template>
    <div class="relative bg-card border border-input focus-within:ring-2 focus-within:ring-primary-500 rounded-xl overflow-hidden transition-all shadow-2xs">
        <!-- Toolbar Lengkap -->
        <div
            v-if="editor"
            class="bg-muted/40 border-b border-input p-2 flex flex-wrap items-center gap-1 sticky top-0 z-10 backdrop-blur-xs"
        >
            <!-- Heading & Paragraph -->
            <button
                type="button"
                @click="editor.chain().focus().setParagraph().run()"
                :class="buttonClass(editor.isActive('paragraph') && !editor.isActive('heading'))"
                title="Paragraf Normal"
            >
                <Pilcrow class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="buttonClass(editor.isActive('heading', { level: 1 }))"
                title="Judul Utama (H1)"
            >
                <Heading1 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="buttonClass(editor.isActive('heading', { level: 2 }))"
                title="Sub Judul (H2)"
            >
                <Heading2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="buttonClass(editor.isActive('heading', { level: 3 }))"
                title="Bagian Kecil (H3)"
            >
                <Heading3 class="h-4 w-4" />
            </button>

            <span class="h-4 w-px bg-border mx-1" />

            <!-- Gaya Karakter -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :disabled="!editor.can().chain().focus().toggleBold().run()"
                :class="buttonClass(editor.isActive('bold'))"
                title="Tebal (Ctrl+B)"
            >
                <BoldIcon class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :disabled="!editor.can().chain().focus().toggleItalic().run()"
                :class="buttonClass(editor.isActive('italic'))"
                title="Miring (Ctrl+I)"
            >
                <ItalicIcon class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="buttonClass(editor.isActive('underline'))"
                title="Garis Bawah (Ctrl+U)"
            >
                <UnderlineIcon class="h-4 w-4" />
            </button>

            <span class="h-4 w-px bg-border mx-1" />

            <!-- Daftar & Format Blok -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="buttonClass(editor.isActive('bulletList'))"
                title="Daftar Poin (Bullet List)"
            >
                <List class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="buttonClass(editor.isActive('orderedList'))"
                title="Daftar Langkah Nomor (Ordered List)"
            >
                <ListOrdered class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="buttonClass(editor.isActive('blockquote'))"
                title="Kutipan Penting / Catatan"
            >
                <TextQuote class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                :class="buttonClass(editor.isActive('codeBlock'))"
                title="Blok Kode / Formula MT4"
            >
                <Code2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors cursor-pointer"
                title="Garis Pembatas Horizontal"
            >
                <Minus class="h-4 w-4" />
            </button>

            <span class="h-4 w-px bg-border mx-1" />

            <!-- Media & Link -->
            <button
                type="button"
                @click="openLinkModal"
                :class="buttonClass(editor.isActive('link'))"
                title="Sisipkan / Edit Tautan (Link)"
            >
                <Link2 class="h-4 w-4" />
            </button>
            <button
                v-if="editor.isActive('link')"
                type="button"
                @click="removeLink"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-destructive hover:bg-destructive/10 transition-colors cursor-pointer"
                title="Hapus Tautan"
            >
                <Unlink class="h-4 w-4" />
            </button>

            <button
                type="button"
                @click="openImageModal"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors cursor-pointer"
                title="Sisipkan Gambar (Upload / URL)"
            >
                <ImageIcon class="h-4 w-4" />
            </button>

            <button
                type="button"
                @click="openVideoModal"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors cursor-pointer"
                title="Sisipkan Video (YouTube, Google Drive, Vimeo, Loom)"
            >
                <VideoIcon class="h-4 w-4" />
            </button>

            <span class="h-4 w-px bg-border mx-1" />

            <!-- History & Bersihkan -->
            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().chain().focus().undo().run()"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors disabled:opacity-40 cursor-pointer"
                title="Batalkan (Undo)"
            >
                <Undo2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().chain().focus().redo().run()"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors disabled:opacity-40 cursor-pointer"
                title="Ulangi (Redo)"
            >
                <Redo2 class="h-4 w-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().unsetAllMarks().clearNodes().run()"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted/80 hover:text-foreground transition-colors cursor-pointer"
                title="Bersihkan Semua Format"
            >
                <Eraser class="h-4 w-4" />
            </button>
        </div>

        <!-- Area Editor Tiptap -->
        <EditorContent :editor="editor" />

        <!-- Status Bar Editor (Statistik & Info) -->
        <div class="px-4 py-2 border-t border-border bg-muted/20 flex flex-wrap items-center justify-between text-[11px] text-muted-foreground gap-2 select-none">
            <div class="flex items-center gap-3">
                <span>{{ wordCount }} kata</span>
                <span>•</span>
                <span>{{ characterCount }} karakter</span>
                <span>•</span>
                <span>~{{ readingTime }} menit waktu baca</span>
            </div>
        </div>

        <!-- MODAL: SISIPKAN TAUTAN (LINK) -->
        <div
            v-if="showLinkModal"
            class="fixed inset-0 z-50 bg-background/80 backdrop-blur-2xs flex items-center justify-center p-4"
        >
            <div class="bg-card border border-border rounded-xl shadow-lg w-full max-w-md p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h4 class="text-sm font-bold text-foreground flex items-center gap-2">
                        <Link2 class="w-4 h-4 text-primary-600" />
                        <span>Sisipkan Tautan Web</span>
                    </h4>
                    <button
                        type="button"
                        @click="showLinkModal = false"
                        class="text-muted-foreground hover:text-foreground p-1 rounded-lg"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-medium text-foreground mb-1">URL Tujuan</label>
                        <input
                            v-model="linkUrl"
                            type="text"
                            placeholder="https://example.com/panduan"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @keydown.enter.prevent="applyLink"
                        />
                    </div>
                    <div v-if="editor.state.selection.empty">
                        <label class="block font-medium text-foreground mb-1">Teks Tautan</label>
                        <input
                            v-model="linkText"
                            type="text"
                            placeholder="Klik di sini untuk membaca..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @keydown.enter.prevent="applyLink"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button variant="outline" size="sm" @click="showLinkModal = false">Batal</Button>
                    <Button size="sm" @click="applyLink">Terapkan Tautan</Button>
                </div>
            </div>
        </div>

        <!-- MODAL: SISIPKAN GAMBAR (UPLOAD ATAU URL) -->
        <div
            v-if="showImageModal"
            class="fixed inset-0 z-50 bg-background/80 backdrop-blur-2xs flex items-center justify-center p-4"
        >
            <div class="bg-card border border-border rounded-xl shadow-lg w-full max-w-md p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h4 class="text-sm font-bold text-foreground flex items-center gap-2">
                        <ImageIcon class="w-4 h-4 text-primary-600" />
                        <span>Sisipkan Gambar Media</span>
                    </h4>
                    <button
                        type="button"
                        @click="showImageModal = false"
                        class="text-muted-foreground hover:text-foreground p-1 rounded-lg"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Tabs Gambar -->
                <div class="flex border-b border-border text-xs font-semibold">
                    <button
                        type="button"
                        @click="imageTab = 'upload'"
                        :class="[
                            'py-2 px-3 border-b-2 transition-colors cursor-pointer',
                            imageTab === 'upload'
                                ? 'border-primary-600 text-primary-600 font-bold'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Unggah dari Komputer
                    </button>
                    <button
                        type="button"
                        @click="imageTab = 'url'"
                        :class="[
                            'py-2 px-3 border-b-2 transition-colors cursor-pointer',
                            imageTab === 'url'
                                ? 'border-primary-600 text-primary-600 font-bold'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Tautan URL Gambar
                    </button>
                </div>

                <!-- Tab 1: Upload File -->
                <div v-if="imageTab === 'upload'" class="space-y-3 text-xs">
                    <div
                        class="border-2 border-dashed border-input hover:border-primary-500 rounded-xl p-6 text-center transition-colors bg-muted/20"
                    >
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                            class="hidden"
                            @change="handleFileUpload"
                        />
                        <div v-if="isUploadingImage" class="space-y-2 py-4">
                            <Loader2 class="w-8 h-8 animate-spin text-primary-600 mx-auto" />
                            <p class="font-medium text-foreground">Mengunggah gambar...</p>
                        </div>
                        <div v-else class="space-y-2 cursor-pointer" @click="fileInputRef?.click()">
                            <UploadCloud class="w-8 h-8 text-muted-foreground mx-auto" />
                            <p class="font-semibold text-foreground">Pilih berkas gambar</p>
                            <p class="text-[11px] text-muted-foreground">PNG, JPG, WEBP, atau GIF (Maks. 2MB)</p>
                            <Button type="button" variant="outline" size="sm" class="mt-2 pointer-events-none">
                                Telusuri Berkas
                            </Button>
                        </div>
                    </div>

                    <p v-if="uploadError" class="text-xs text-destructive font-medium">
                        {{ uploadError }}
                    </p>

                    <div>
                        <label class="block font-medium text-foreground mb-1">Teks Keterangan (Alt Text - Opsional)</label>
                        <input
                            v-model="imageAlt"
                            type="text"
                            placeholder="Deskripsi singkat gambar..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                </div>

                <!-- Tab 2: Direct Image URL -->
                <div v-else class="space-y-3 text-xs">
                    <div>
                        <label class="block font-medium text-foreground mb-1">URL Berkas Gambar</label>
                        <input
                            v-model="imageUrl"
                            type="text"
                            placeholder="https://example.com/gambar-panduan.png"
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                    <div>
                        <label class="block font-medium text-foreground mb-1">Teks Keterangan (Alt Text)</label>
                        <input
                            v-model="imageAlt"
                            type="text"
                            placeholder="Deskripsi singkat gambar..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                    <div class="flex justify-end pt-2">
                        <Button size="sm" :disabled="!imageUrl.trim()" @click="applyImageUrl">
                            Sisipkan Gambar
                        </Button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button variant="outline" size="sm" @click="showImageModal = false">Tutup</Button>
                </div>
            </div>
        </div>

        <!-- MODAL: SISIPKAN VIDEO EMBED (YouTube, Google Drive, Vimeo, Loom) -->
        <div
            v-if="showVideoModal"
            class="fixed inset-0 z-50 bg-background/80 backdrop-blur-2xs flex items-center justify-center p-4"
        >
            <div class="bg-card border border-border rounded-xl shadow-lg w-full max-w-lg p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h4 class="text-sm font-bold text-foreground flex items-center gap-2">
                        <VideoIcon class="w-4 h-4 text-primary-600" />
                        <span>Sisipkan Video Panduan</span>
                    </h4>
                    <button
                        type="button"
                        @click="showVideoModal = false"
                        class="text-muted-foreground hover:text-foreground p-1 rounded-lg"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-medium text-foreground mb-1">
                            Tautan URL Video (YouTube, Google Drive, Vimeo, atau Loom)
                        </label>
                        <input
                            v-model="videoInputUrl"
                            type="text"
                            placeholder="Contoh: https://drive.google.com/file/d/... atau https://youtu.be/..."
                            class="w-full px-3 py-2 rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono text-xs placeholder:font-sans"
                            @keydown.enter.prevent="applyVideo"
                        />
                    </div>

                    <!-- Badge Provider Terdeteksi -->
                    <div v-if="detectedVideo" class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <Check class="w-3 h-3" />
                            Sumber Video: {{ detectedVideo.provider }}
                        </span>
                    </div>

                    <!-- Info Khusus Google Drive -->
                    <div
                        v-if="detectedVideo?.isGoogleDrive"
                        class="p-3 rounded-lg bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 text-blue-800 dark:text-blue-300 text-[11px] flex items-start gap-2 leading-relaxed"
                    >
                        <Info class="w-4 h-4 shrink-0 text-blue-600 mt-0.5" />
                        <div>
                            <strong>Tips Berbagi Google Drive:</strong> Pastikan setelan akses berbagi file di Google Drive diubah menjadi <em>"Siapa saja yang memiliki link"</em> (Anyone with the link) agar video dapat diputar langsung oleh investor tanpa perlu login ke akun Google.
                        </div>
                    </div>

                    <p v-if="videoError" class="text-xs text-destructive font-medium">
                        {{ videoError }}
                    </p>

                    <!-- Pratinjau Video Instan jika URL terdeteksi -->
                    <div v-if="detectedVideo?.embedUrl" class="space-y-1.5 pt-1">
                        <label class="block font-medium text-muted-foreground text-[11px]">
                            Pratinjau Pemutar Video:
                        </label>
                        <div class="relative w-full aspect-video rounded-lg overflow-hidden bg-slate-950 border border-border shadow-xs">
                            <iframe
                                :src="detectedVideo.embedUrl"
                                title="Video preview"
                                frameborder="0"
                                allowfullscreen
                                class="absolute inset-0 w-full h-full"
                            ></iframe>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                    <Button variant="outline" size="sm" @click="showVideoModal = false">Batal</Button>
                    <Button size="sm" :disabled="!detectedVideo" @click="applyVideo">
                        Sisipkan Video ke Artikel
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
