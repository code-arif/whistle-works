<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import {
  Bold,
  Italic,
  Underline as UnderlineIcon,
  Strikethrough,
  Heading1,
  Heading2,
  Heading3,
  List,
  ListOrdered,
  Quote,
  Code,
  Minus,
  Link as LinkIcon,
  Unlink,
  Undo,
  Redo,
  RemoveFormatting
} from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Write comprehensive policy content here...'
  },
  minHeight: {
    type: String,
    default: 'min-h-[360px]'
  }
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3],
      },
    }),
    Underline,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-[#F29F67] hover:underline cursor-pointer',
        target: '_blank',
        rel: 'noopener noreferrer'
      },
    }),
  ],
  editorProps: {
    attributes: {
      class: 'prose dark:prose-invert max-w-none focus:outline-none text-sm text-slate-800 dark:text-slate-100 leading-relaxed font-sans',
    },
  },
  onUpdate: () => {
    emit('update:modelValue', editor.value.getHTML());
  },
});

// Sync external changes (e.g., tab switch or reset)
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue || '', false);
  }
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const setLink = () => {
  if (!editor.value) return;
  const previousUrl = editor.value.getAttributes('link').href;
  const url = window.prompt('Enter Web Address / URL:', previousUrl || 'https://');

  if (url === null) return;

  if (url === '' || url === 'https://') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    return;
  }

  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const wordCount = computed(() => {
  if (!editor.value) return 0;
  const text = editor.value.getText().trim();
  if (!text) return 0;
  return text.split(/\s+/).length;
});

const charCount = computed(() => {
  if (!editor.value) return 0;
  return editor.value.getText().length;
});
</script>

<template>
  <div class="border border-slate-200 dark:border-white/[0.08] rounded-xl overflow-hidden bg-white dark:bg-[#1E1E2C] shadow-sm transition-all focus-within:ring-2 focus-within:ring-[#F29F67]/40 focus-within:border-[#F29F67]">
    
    <!-- Editor Toolbar -->
    <div v-if="editor" class="bg-slate-50/80 dark:bg-[#161622] border-b border-slate-200 dark:border-white/[0.08] p-2 flex flex-wrap items-center gap-1 text-xs select-none">
      
      <!-- Headings -->
      <div class="flex items-center gap-0.5 pr-1 border-r border-slate-200 dark:border-white/[0.08]">
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="[
            editor.isActive('heading', { level: 1 })
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Heading 1 (H1)"
        >
          <Heading1 class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="[
            editor.isActive('heading', { level: 2 })
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Heading 2 (H2)"
        >
          <Heading2 class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="[
            editor.isActive('heading', { level: 3 })
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Heading 3 (H3)"
        >
          <Heading3 class="w-4 h-4" />
        </button>
      </div>

      <!-- Basic Formatting: Bold, Italic, Underline, Strike -->
      <div class="flex items-center gap-0.5 px-1 border-r border-slate-200 dark:border-white/[0.08]">
        <button
          type="button"
          @click="editor.chain().focus().toggleBold().run()"
          :class="[
            editor.isActive('bold')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Bold (Ctrl+B)"
        >
          <Bold class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleItalic().run()"
          :class="[
            editor.isActive('italic')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Italic (Ctrl+I)"
        >
          <Italic class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleUnderline().run()"
          :class="[
            editor.isActive('underline')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Underline (Ctrl+U)"
        >
          <UnderlineIcon class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleStrike().run()"
          :class="[
            editor.isActive('strike')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Strikethrough"
        >
          <Strikethrough class="w-4 h-4" />
        </button>
      </div>

      <!-- Lists & Quotes -->
      <div class="flex items-center gap-0.5 px-1 border-r border-slate-200 dark:border-white/[0.08]">
        <button
          type="button"
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="[
            editor.isActive('bulletList')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Bullet List"
        >
          <List class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="[
            editor.isActive('orderedList')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Numbered List"
        >
          <ListOrdered class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="[
            editor.isActive('blockquote')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Quote Block"
        >
          <Quote class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleCodeBlock().run()"
          :class="[
            editor.isActive('codeBlock')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Code Block"
        >
          <Code class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().setHorizontalRule().run()"
          class="p-1.5 rounded-md text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06] transition-colors"
          title="Horizontal Line"
        >
          <Minus class="w-4 h-4" />
        </button>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-0.5 px-1 border-r border-slate-200 dark:border-white/[0.08]">
        <button
          type="button"
          @click="setLink"
          :class="[
            editor.isActive('link')
              ? 'bg-[#F29F67]/20 text-[#F29F67] font-bold shadow-2xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06]',
            'p-1.5 rounded-md transition-colors'
          ]"
          title="Insert Link"
        >
          <LinkIcon class="w-4 h-4" />
        </button>

        <button
          v-if="editor.isActive('link')"
          type="button"
          @click="editor.chain().focus().unsetLink().run()"
          class="p-1.5 rounded-md text-rose-500 hover:bg-rose-500/10 transition-colors"
          title="Remove Link"
        >
          <Unlink class="w-4 h-4" />
        </button>
      </div>

      <!-- Undo / Redo / Clear -->
      <div class="flex items-center gap-0.5 pl-1 ml-auto">
        <button
          type="button"
          @click="editor.chain().focus().undo().run()"
          :disabled="!editor.can().undo()"
          class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06] disabled:opacity-30 disabled:pointer-events-none transition-colors"
          title="Undo (Ctrl+Z)"
        >
          <Undo class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().redo().run()"
          :disabled="!editor.can().redo()"
          class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/[0.06] disabled:opacity-30 disabled:pointer-events-none transition-colors"
          title="Redo (Ctrl+Y)"
        >
          <Redo class="w-4 h-4" />
        </button>

        <button
          type="button"
          @click="editor.chain().focus().clearNodes().unsetAllMarks().run()"
          class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition-colors"
          title="Clear Formatting"
        >
          <RemoveFormatting class="w-4 h-4" />
        </button>
      </div>

    </div>

    <!-- Editor Surface Area -->
    <div :class="['p-5 sm:p-6 overflow-y-auto', minHeight]">
      <EditorContent :editor="editor" class="focus:outline-none" />
    </div>

    <!-- Status Bar / Metrics Ribbon -->
    <div class="px-4 py-2 bg-slate-50/60 dark:bg-[#161622]/60 border-t border-slate-200 dark:border-white/[0.08] flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
      <div class="flex items-center gap-3">
        <span>Words: <strong class="text-slate-700 dark:text-slate-300">{{ wordCount }}</strong></span>
        <span>Characters: <strong class="text-slate-700 dark:text-slate-300">{{ charCount }}</strong></span>
      </div>
      <div class="flex items-center gap-1.5 text-[10px]">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        <span>Rich Text Active</span>
      </div>
    </div>

  </div>
</template>

<style>
/* Custom typography styles inside the editor surface */
.ProseMirror {
  outline: none;
  min-height: 320px;
}

.ProseMirror p {
  margin-bottom: 0.85rem;
  line-height: 1.65;
}

.ProseMirror h1 {
  font-size: 1.65rem;
  font-weight: 700;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
}

.ProseMirror h2 {
  font-size: 1.35rem;
  font-weight: 700;
  margin-top: 1.25rem;
  margin-bottom: 0.65rem;
  letter-spacing: -0.015em;
}

.ProseMirror h3 {
  font-size: 1.15rem;
  font-weight: 600;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.ProseMirror ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 0.85rem;
}

.ProseMirror ol {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 0.85rem;
}

.ProseMirror li {
  margin-bottom: 0.25rem;
}

.ProseMirror blockquote {
  border-left: 3px solid #F29F67;
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: #94a3b8;
}

.ProseMirror pre {
  background-color: #14141e;
  color: #f8fafc;
  padding: 0.85rem 1rem;
  border-radius: 0.375rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.825rem;
  margin-bottom: 0.85rem;
  overflow-x: auto;
}

.ProseMirror hr {
  border: 0;
  border-top: 1px solid rgba(148, 163, 184, 0.25);
  margin: 1.5rem 0;
}
</style>
