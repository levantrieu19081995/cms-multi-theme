import * as pdfjsLib from 'pdfjs-dist/build/pdf';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

let renderVersion = 0;
let activeDocument = null;

function loadingMessage(container) {
  container.replaceChildren();
  const message = document.createElement('div');
  message.className = 'cms-pdf-message';
  message.innerHTML = '<i class="ri-loader-4-line" aria-hidden="true"></i><span>Đang tải nội dung tập tin...</span>';
  container.append(message);
}

function errorMessage(container, text = 'Không thể hiển thị tập tin này. Vui lòng tải tập tin để xem.') {
  container.replaceChildren();
  const message = document.createElement('div');
  message.className = 'cms-pdf-message cms-pdf-message-error';
  message.textContent = text;
  container.append(message);
}

function unsupportedPreview(container, fileName) {
  container.replaceChildren();
  const message = document.createElement('div');
  message.className = 'cms-file-fallback';

  const icon = document.createElement('i');
  icon.className = 'ri-file-3-line';
  icon.setAttribute('aria-hidden', 'true');

  const title = document.createElement('strong');
  title.textContent = fileName || 'Tập tin đính kèm';

  const note = document.createElement('p');
  note.textContent = 'Định dạng này chưa hỗ trợ xem trực tiếp trên trình duyệt. Bạn có thể dùng nút Tải xuống để mở toàn bộ nội dung.';

  message.append(icon, title, note);
  container.append(message);
}

async function renderImage(url, fileName, container) {
  renderVersion += 1;
  await destroyActiveDocument();
  container.replaceChildren();

  const image = document.createElement('img');
  image.className = 'cms-file-image-preview';
  image.src = url;
  image.alt = fileName || 'Tập tin hình ảnh đính kèm';
  image.addEventListener('error', () => errorMessage(container), { once: true });
  container.append(image);
}

async function renderFile(url, fileName, mimeType, container) {
  const type = String(mimeType || '').toLowerCase();
  if (type.includes('pdf') || /\.pdf(?:$|[?#])/i.test(url)) {
    await renderAllPages(url, container);
    return;
  }

  if (type.startsWith('image/')) {
    await renderImage(url, fileName, container);
    return;
  }

  renderVersion += 1;
  await destroyActiveDocument();
  unsupportedPreview(container, fileName);
}

async function destroyActiveDocument() {
  if (!activeDocument) return;
  try {
    await activeDocument.destroy();
  } catch (_) {
    // The document can already be closing when the modal is dismissed during loading.
  }
  activeDocument = null;
}

async function renderAllPages(url, container) {
  const version = ++renderVersion;
  await destroyActiveDocument();
  loadingMessage(container);

  try {
    const loadingTask = pdfjsLib.getDocument({ url });
    const pdf = await loadingTask.promise;
    if (version !== renderVersion) {
      await pdf.destroy();
      return;
    }

    activeDocument = pdf;
    container.replaceChildren();

    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
      if (version !== renderVersion) return;

      const page = await pdf.getPage(pageNumber);
      const initialViewport = page.getViewport({ scale: 1 });
      const availableWidth = Math.max(280, container.clientWidth - 48);
      const scale = Math.min(2, availableWidth / initialViewport.width);
      const viewport = page.getViewport({ scale });
      const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

      const pageElement = document.createElement('section');
      pageElement.className = 'cms-pdf-page';
      pageElement.setAttribute('aria-label', `Trang ${pageNumber} trên ${pdf.numPages}`);

      const canvas = document.createElement('canvas');
      canvas.width = Math.floor(viewport.width * pixelRatio);
      canvas.height = Math.floor(viewport.height * pixelRatio);
      canvas.style.width = `${Math.floor(viewport.width)}px`;
      canvas.style.height = `${Math.floor(viewport.height)}px`;
      pageElement.append(canvas);
      container.append(pageElement);

      const context = canvas.getContext('2d', { alpha: false });
      const transform = pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0];
      await page.render({ canvasContext: context, viewport, transform }).promise;
      page.cleanup();
    }
  } catch (error) {
    if (version !== renderVersion) return;
    console.error('Không thể hiển thị PDF:', error);
    errorMessage(container);
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const modalElement = document.getElementById('cmsDocumentModal');
  const container = document.getElementById('filePreviewContainer');
  const title = document.getElementById('cmsDocumentModalTitle');
  const download = document.getElementById('cmsDocumentDownload');
  const links = document.querySelectorAll('.cms-attachment-link[data-file-url]');
  if (!modalElement || !container || !links.length) return;

  const modal = window.bootstrap?.Modal.getOrCreateInstance(modalElement);

  links.forEach(link => link.addEventListener('click', () => {
    const url = link.dataset.fileUrl;
    if (!url) return;

    const fileName = link.dataset.fileName || 'Xem tập tin';
    const fileType = link.dataset.fileType || '';
    title.textContent = fileName;
    download.href = url;

    modalElement.addEventListener('shown.bs.modal', () => renderFile(url, fileName, fileType, container), { once: true });
    if (modal) modal.show();
  }));

  modalElement.addEventListener('hidden.bs.modal', () => {
    renderVersion += 1;
    container.replaceChildren();
    destroyActiveDocument();
  });
});
