// Giảm chuyển động của hệ điều hành. Mỗi effect tự quyết định có theo hay không (reveal vẫn chạy như trước, auth thì bỏ qua hoạt ảnh).
export const prefersReducedMotion = () => !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
