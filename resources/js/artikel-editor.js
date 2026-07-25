// Editor artikel admin: CKEditor 5 (build lengkap, di-bundle Vite) + crop gambar sampul.
import {
    ClassicEditor,
    Essentials, Paragraph, Autoformat, PasteFromOffice,
    Heading, Bold, Italic, Underline, Strikethrough, RemoveFormat, Highlight,
    Link, LinkImage, List, ListProperties, BlockQuote, Indent, IndentBlock,
    HorizontalLine, CodeBlock, FindAndReplace, SourceEditing, WordCount,
    Image, ImageToolbar, ImageCaption, ImageStyle, ImageResize, ImageUpload, ImageInsert, AutoImage, PictureEditing,
    Table, TableToolbar, TableColumnResize, TableCaption,
    SimpleUploadAdapter,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function initEditor() {
    const el = document.querySelector('#body-editor');
    if (!el) return;

    ClassicEditor.create(el, {
        licenseKey: 'GPL',
        plugins: [
            Essentials, Paragraph, Autoformat, PasteFromOffice,
            Heading, Bold, Italic, Underline, Strikethrough, RemoveFormat, Highlight,
            Link, LinkImage, List, ListProperties, BlockQuote, Indent, IndentBlock,
            HorizontalLine, CodeBlock, FindAndReplace, SourceEditing, WordCount,
            Image, ImageToolbar, ImageCaption, ImageStyle, ImageResize, ImageUpload, ImageInsert, AutoImage, PictureEditing,
            Table, TableToolbar, TableColumnResize, TableCaption,
            SimpleUploadAdapter,
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|', 'sourceEditing', 'findAndReplace', '|',
                'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'highlight', 'removeFormat', '|',
                'link', 'blockQuote', 'insertImage', 'insertTable', 'codeBlock', 'horizontalLine', '|',
                'bulletedList', 'numberedList', 'outdent', 'indent',
            ],
            shouldNotGroupWhenFull: true,
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraf', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Judul', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Subjudul', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Subjudul kecil', class: 'ck-heading_heading4' },
            ],
        },
        image: {
            toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'linkImage'],
        },
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableCaption'] },
        link: { defaultProtocol: 'https://', addTargetToExternalLinks: true },
        simpleUpload: {
            uploadUrl: el.dataset.uploadUrl,
            withCredentials: true,
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        },
    }).then((editor) => {
        // Tulis balik ke textarea sebelum submit.
        editor.sourceElement.form?.addEventListener('submit', () => editor.updateSourceElement());

        // Hitung kata.
        const box = document.querySelector('#editor-wordcount');
        if (box) box.appendChild(editor.plugins.get('WordCount').wordCountContainer);
    }).catch((err) => console.error(err));
}

function initCover() {
    const input = document.querySelector('#cover-input');
    const preview = document.querySelector('#cover-preview');
    const dialog = document.querySelector('#crop-dialog');
    const image = document.querySelector('#crop-image');
    if (!input || !dialog || !image) return;

    let cropper = null;
    let objectUrl = null;

    const closeDialog = () => {
        cropper?.destroy();
        cropper = null;
        dialog.close();
    };

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        image.src = URL.createObjectURL(file);
        dialog.showModal();
        cropper?.destroy();
        cropper = new Cropper(image, { aspectRatio: 3 / 2, viewMode: 1, autoCropArea: 1, background: false });
    });

    document.querySelector('#crop-cancel')?.addEventListener('click', () => {
        input.value = '';
        closeDialog();
    });

    document.querySelector('#crop-apply')?.addEventListener('click', () => {
        cropper?.getCroppedCanvas({ maxWidth: 1600, maxHeight: 1600 }).toBlob((blob) => {
            const file = new File([blob], 'sampul.jpg', { type: 'image/jpeg' });
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            if (preview) {
                preview.src = objectUrl;
                preview.classList.remove('hidden');
            }
            closeDialog();
        }, 'image/jpeg', 0.9);
    });
}

initEditor();
initCover();
