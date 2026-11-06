import {
  ClassicEditor,
  AccessibilityHelp,
  Alignment,
  Autoformat,
  AutoImage,
  AutoLink,
  Autosave,
  BalloonToolbar,
  BlockQuote,
  Bold,
  Code,
  Essentials,
  FindAndReplace,
  FontBackgroundColor,
  FontColor,
  FontFamily,
  FontSize,
  FullPage,
  GeneralHtmlSupport,
  Heading,
  Highlight,
  HorizontalLine,
  HtmlComment,
  HtmlEmbed,
  ImageBlock,
  ImageCaption,
  ImageInline,
  ImageInsert,
  ImageInsertViaUrl,
  ImageResize,
  ImageStyle,
  ImageTextAlternative,
  ImageToolbar,
  ImageUpload,
  Indent,
  IndentBlock,
  Italic,
  Link,
  LinkImage,
  List,
  ListProperties,
  Markdown,
  MediaEmbed,
  Mention,
  PageBreak,
  Paragraph,
  PasteFromMarkdownExperimental,
  PasteFromOffice,
  RemoveFormat,
  SelectAll,
  ShowBlocks,
  SimpleUploadAdapter,
  SourceEditing,
  SpecialCharacters,
  SpecialCharactersArrows,
  SpecialCharactersCurrency,
  SpecialCharactersEssentials,
  SpecialCharactersLatin,
  SpecialCharactersMathematical,
  SpecialCharactersText,
  Strikethrough,
  Style,
  Subscript,
  Superscript,
  Table,
  TableCaption,
  TableCellProperties,
  TableColumnResize,
  TableProperties,
  TableToolbar,
  TextTransformation,
  TodoList,
  Underline,
  Undo,
  FileRepository
} from 'ckeditor5';
import translations from 'ckeditor5/translations/vi.js';
import MyUploadAdapter from '../../../../../../../resources/assets/plugin/ck/UploadAdapter';
// import FileManagerCustom from '../../../plugin/ck/FileManagerCustom';
const editorConfig = {
  toolbar: {
    items: [
      'undo',
      'redo',
      '|',
      'sourceEditing',
      'showBlocks',
      'findAndReplace',
      '|',
      'heading',
      'style',
      '|',
      'fontSize',
      'fontFamily',
      'fontColor',
      'fontBackgroundColor',
      '|',
      'bold',
      'italic',
      'underline',
      'strikethrough',
      'subscript',
      'superscript',
      'code',
      'removeFormat',
      '|',
      'specialCharacters',
      'horizontalLine',
      'pageBreak',
      'link',

      'insertImage',
      'insertImageViaUrl',
      'mediaEmbed',
      'insertTable',
      'highlight',
      'blockQuote',
      'htmlEmbed',
      '|',
      'alignment',
      '|',
      'bulletedList',
      'numberedList',
      'todoList',
      'outdent',
      'indent'
    ],
    shouldNotGroupWhenFull: false
  },
  plugins: [
    AccessibilityHelp,
    Alignment,
    Autoformat,
    AutoImage,
    AutoLink,
    Autosave,
    BalloonToolbar,
    BlockQuote,
    Bold,
    Code,
    Essentials,
    FindAndReplace,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    FullPage,
    GeneralHtmlSupport,
    Heading,
    Highlight,
    HorizontalLine,
    HtmlComment,
    HtmlEmbed,
    ImageBlock,
    ImageCaption,
    ImageInline,
    ImageInsert,
    ImageInsertViaUrl,
    ImageResize,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    LinkImage,
    List,
    ListProperties,
    Markdown,
    MediaEmbed,
    Mention,
    PageBreak,
    Paragraph,
    PasteFromMarkdownExperimental,
    PasteFromOffice,
    RemoveFormat,
    SelectAll,
    ShowBlocks,
    SimpleUploadAdapter,
    SourceEditing,
    SpecialCharacters,
    SpecialCharactersArrows,
    SpecialCharactersCurrency,
    SpecialCharactersEssentials,
    SpecialCharactersLatin,
    SpecialCharactersMathematical,
    SpecialCharactersText,
    Strikethrough,
    Style,
    Subscript,
    Superscript,
    Table,
    TableCaption,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    TextTransformation,
    TodoList,
    Underline,
    Undo,
    FileRepository
  ],
  balloonToolbar: ['bold', 'italic', '|', 'link', 'insertImage', '|', 'bulletedList', 'numberedList'],
  fontFamily: {
    supportAllValues: true
  },
  fontSize: {
    options: [10, 12, 14, 'default', 18, 20, 22],
    supportAllValues: true
  },

  heading: {
    options: [
      {
        model: 'paragraph',
        title: 'Paragraph',
        class: 'ck-heading_paragraph'
      },
      {
        model: 'heading1',
        view: 'h1',
        title: 'Heading 1',
        class: 'ck-heading_heading1'
      },
      {
        model: 'heading2',
        view: 'h2',
        title: 'Heading 2',
        class: 'ck-heading_heading2'
      },
      {
        model: 'heading3',
        view: 'h3',
        title: 'Heading 3',
        class: 'ck-heading_heading3'
      },
      {
        model: 'heading4',
        view: 'h4',
        title: 'Heading 4',
        class: 'ck-heading_heading4'
      },
      {
        model: 'heading5',
        view: 'h5',
        title: 'Heading 5',
        class: 'ck-heading_heading5'
      },
      {
        model: 'heading6',
        view: 'h6',
        title: 'Heading 6',
        class: 'ck-heading_heading6'
      }
    ]
  },
  // htmlSupport: {
  //   allow: [
  //     {
  //       name: /^.*$/,
  //       styles: true,
  //       attributes: true,
  //       classes: true
  //     }
  //   ]
  // },
  // image: {
  //   toolbar: [
  //     'toggleImageCaption',
  //     'imageTextAlternative',
  //     '|',
  //     'imageStyle:inline',
  //     'imageStyle:wrapText',
  //     'imageStyle:breakText',
  //     '|',
  //     'resizeImage'
  //   ]
  // },

  image: {
    toolbar: [
      'toggleImageCaption',
      'imageTextAlternative',
      '|',
      'imageStyle:alignLeft',
      'imageStyle:alignCenter',
      'imageStyle:alignRight',
      '|',
      'imageStyle:inline',
      'imageStyle:wrapText',
      'imageStyle:breakText',
      '|',
      'resizeImage'
    ],

    // Cho phép các style của ImageStyle
    styles: ['alignLeft', 'alignCenter', 'alignRight', 'inline', 'wrapText', 'breakText'],

    // Lưu width inline theo PX khi kéo resize
    resizeUnit: 'px',
    resizeOptions: [
      { name: 'resizeImage:original', label: '100%', value: null },
      { name: 'resizeImage:800', label: '800px', value: '800' },
      { name: 'resizeImage:600', label: '600px', value: '600' },
      { name: 'resizeImage:400', label: '400px', value: '400' }
    ]
  },

  htmlSupport: {
    allow: [
      { name: 'img', styles: true, attributes: true, classes: true },
      { name: 'figure', styles: true, attributes: true, classes: true },
      { name: /^.*$/, styles: true, attributes: true, classes: true }
    ]
  },

  initialData: '',
  language: 'vi',
  link: {
    addTargetToExternalLinks: true,
    defaultProtocol: 'https://',
    decorators: {
      toggleDownloadable: {
        mode: 'manual',
        label: 'Downloadable',
        attributes: {
          download: 'file'
        }
      }
    }
  },
  list: {
    properties: {
      styles: true,
      startIndex: true,
      reversed: true
    }
  },
  mention: {
    feeds: [
      {
        marker: '@',
        feed: [
          /* See: https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html */
        ]
      }
    ]
  },
  menuBar: {
    isVisible: false
  },
  // placeholder: 'Type or paste your content here!',
  style: {
    definitions: [
      {
        name: 'Article category',
        element: 'h3',
        classes: ['category']
      },
      {
        name: 'Title',
        element: 'h2',
        classes: ['document-title']
      },
      {
        name: 'Subtitle',
        element: 'h3',
        classes: ['document-subtitle']
      },
      {
        name: 'Info box',
        element: 'p',
        classes: ['info-box']
      },
      {
        name: 'Side quote',
        element: 'blockquote',
        classes: ['side-quote']
      },
      {
        name: 'Marker',
        element: 'span',
        classes: ['marker']
      },
      {
        name: 'Spoiler',
        element: 'span',
        classes: ['spoiler']
      },
      {
        name: 'Code (dark)',
        element: 'pre',
        classes: ['fancy-code', 'fancy-code-dark']
      },
      {
        name: 'Code (bright)',
        element: 'pre',
        classes: ['fancy-code', 'fancy-code-bright']
      }
    ]
  },
  table: {
    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
  },
  translations: [translations]
};

// The form uses a compact toolbar while retaining the inherited rich-text tools and HTML support.
const primaryTools = ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'fontColor', '|', 'alignment', 'bulletedList', 'numberedList', 'blockQuote', '|', 'link', 'insertImage', 'mediaEmbed', 'insertTable', 'sourceEditing'];
const advancedTools = editorConfig.toolbar.items.filter(item => item !== '|' && !primaryTools.includes(item));
editorConfig.toolbar.items = [...primaryTools, { label: window.cmsAdminTranslations.more_tools, icon: 'threeVerticalDots', items: advancedTools }];

const startEditorialEditor = () => {
  const input = document.getElementById('ckeditor5');
  if (!input) return;
  const record = input.closest('.cms-record-page');
  if (record && !record.querySelector('.cms-news-heading') && record.dataset.cmsRecordReady !== 'true') {
    record.addEventListener('cms:record-ready', startEditorialEditor, { once: true });
    return;
  }
  ClassicEditor.create(input, editorConfig).then(editor => {
    window.editor = editor;
    editor.plugins.get('FileRepository').createUploadAdapter = loader => new MyUploadAdapter(loader);
    editor.setData(input.value);
  }).catch(error => {
    console.error('Không thể mở trình soạn thảo.', error);
  });
  // Other-language content fields of the bilingual form (textarea[data-cms-editor]); the primary editor above stays window.editor.
  window.cmsEditors = window.cmsEditors || {};
  document.querySelectorAll('textarea[data-cms-editor]').forEach(extra => {
    if (extra.dataset.cmsEditorReady) return;
    extra.dataset.cmsEditorReady = 'true';
    ClassicEditor.create(extra, editorConfig).then(editor => {
      window.cmsEditors[extra.id] = editor;
      editor.plugins.get('FileRepository').createUploadAdapter = loader => new MyUploadAdapter(loader);
      editor.setData(extra.value);
    }).catch(error => {
      console.error('Không thể mở trình soạn thảo cho ngôn ngữ khác.', error);
    });
  });
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startEditorialEditor, { once: true });
else startEditorialEditor();
