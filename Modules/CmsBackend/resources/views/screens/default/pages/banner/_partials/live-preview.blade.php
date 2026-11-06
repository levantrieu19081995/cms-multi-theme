{{-- Live Preview Giao diện Banner --}}
<style>
    :root {
        --primaryColor: #0c57a3;
        /* Main blue */
        --primaryLight: #0066ff;
        --primaryDark: #07305c;

        --secondaryColor: #1070d1;
        /* Lighter blue */
        --secondaryLight: #5bb6f9;
        --secondaryDark: #08508a;

        --accentYellow: #f1c40f;
        /* Yellow accent */
        --accentRed: #d90429;
        /* Red accent */
        --accentGreen: #2ecc40;
        /* Green accent */

        --backgroundColor: #f5faff;
        /* Very light blue/gray */
        --surfaceColor: #ffffff;
        --primaryTextColor: #0167F4;
        --textColor: #222b45;
        --textLight: #6c757d;
        --borderColor: #e0e6ef;
    }

    .banner {
        position: relative;
        overflow: hidden;
        height: 300px;
        background: linear-gradient(120deg, #e0e7ff 0%, #f0f7ff 100%);
    }

    .banner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg,
                rgba(10, 20, 30, 0.92) 65%,
                rgba(94, 231, 223, 0.18) 100%);
        backdrop-filter: blur(2px) brightness(0.8) saturate(1.15);
        box-shadow:
            0 8px 32px 0 rgba(31, 38, 135, 0.22),
            0 0 24px 0 rgba(94, 231, 223, 0.12);
        z-index: 1;
        overflow: hidden;
        transition: background 0.4s, backdrop-filter 0.4s, box-shadow 0.4s;
    }

    .overlay-blob {
        position: absolute;
        top: -60px;
        left: -60px;
        width: 320px;
        height: 320px;
        z-index: 2;
        pointer-events: none;
        opacity: 0.7;
        animation: blobMove 8s infinite alternate ease-in-out;
    }

    @keyframes blobMove {
        0% {
            transform: scale(1) translateY(0);
        }

        100% {
            transform: scale(1.1) translateY(20px);
        }
    }

    .overlay-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 80px;
        z-index: 2;
        pointer-events: none;
        opacity: 0.8;
    }

    /* Shimmer effect for extra beauty */
    .banner-overlay::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(100deg,
                rgba(255, 255, 255, 0) 60%,
                rgba(255, 255, 255, 0.10) 80%,
                rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
        animation: shimmer 2.8s infinite linear;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }


    .banner-content {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        gap: 32px;
        padding: 20px;
    }

    .bg-image-banner {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }

    .content-left {
        text-align: left;
        flex: 1 1 0;
        min-width: 0;
        z-index: 2;
    }

    .text-theme {
        /* color: var(--primaryLight); */
        text-transform: uppercase;
        text-shadow: 2px 4px 12px rgba(58, 141, 222, 0.18), 0 2px 8px rgba(0, 0, 0, 0.9);
        line-height: 1.5;
    }

    h1 {
        font-size: 36px;
        font-weight: 600;
        color: white;
        line-height: 1.2;
    }

    h1 .text-primary {
        color: var(--secondaryColor);
    }

    .btn-start {
        margin-top: 20px;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        border: 0;
        background-color: var(--primaryLight);
        color: white;
        box-shadow: rgb(0 0 0 / 5%) 0 0 8px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
        transition: all 0.5s ease;
        box-shadow: rgba(0, 0, 0, 0.07) 0px 1px 2px, rgba(0, 0, 0, 0.07) 0px 2px 4px, rgba(0, 0, 0, 0.07) 0px 4px 8px, rgba(0, 0, 0, 0.07) 0px 8px 16px, rgba(0, 0, 0, 0.07) 0px 16px 32px, rgba(0, 0, 0, 0.07) 0px 32px 64px;
    }

    .btn-start:hover {
        letter-spacing: 0.5px;
        background-color: hsl(0, 0%, 100%);
        color: var(--secondaryColor);
        box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px, rgba(10, 37, 64, 0.35) 0px -2px 6px 0px inset;
    }

    .btn-start:active {
        letter-spacing: 0.5px;
        background-color: hsl(246, 100%, 50%);
        color: hsl(0, 0%, 100%);
        box-shadow: var(--primaryLight) 0px 0px 0px 0px;
        transform: translateY(5px);
        transition: 100ms;
    }

    .circle-image-wrapper {
        position: relative;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primaryLight) 60%, #5ee7df 100%);
        margin-top: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid transparent;
        background-clip: padding-box, border-box;
        box-shadow:
            0 8px 32px 0 rgba(0, 102, 255, 0.18),
            0 2px 8px 0 rgba(94, 231, 223, 0.12);
        z-index: 2;
        flex-shrink: 0;

    }

    .circle-image-wrapper::before {
        content: "";
        position: absolute;
        inset: -8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #5ee7df 0%, var(--primaryLight) 100%);
        z-index: 0;
        filter: blur(12px);
        opacity: 0.5;
    }

    .circle-image-wrapper img {
        width: 96%;
        height: 96%;
        object-fit: cover;
        border-radius: 50%;
        box-shadow: 0 2px 16px rgba(0, 102, 255, 0.12);
        position: relative;
        z-index: 1;
    }
</style>

{{-- Live Preview Giao diện Banner --}}
<div id="live-banner-preview">
    <div class="swiper banner" id="bannerPreviewSection">
        <div class="swiper-wrapper">
            <div class="swiper-slide bg-image-banner" id="banner-bg">

                {{-- <div class="banner-overlay"></div>
                <div class="banner-content">
                    <div class="content-left banner-text">
                        <h1 class="text-theme" id="preview-title">{{ old('title') ?? ($post->title ?? __('admin.ui.tieu_de')) }}
                        </h1>
                        <h5 class="text-light" id="preview-description">
                            {{ old('description', $post->description ?? __('admin.ui.thong_tin_mo_ta')) }}</h5>
                        <a href="#" class="btn-start" id="preview-btn">
                            {{ old('btn_text', $post->btn_text ?? __('admin.ui.nhan_nut')) }}
                            <i class="fa fa-arrow-right mx-2" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="circle-image-wrapper">
                        <img id="preview-img" />

                    </div>
                </div> --}}
                <div class="banner-overlay">
                    <svg class="banner-svg" viewBox="0 0 1200 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Dải xanh dương nhạt -->
                        <polygon points="0,0 1200,0 1200,80 0,180" fill="url(#blue1)" opacity="0.18" />
                        <!-- Dải xanh dương đậm -->
                        <polygon points="0,120 1200,20 1200,120 0,220" fill="url(#blue2)" opacity="0.15" />
                        <!-- Dải vàng cam -->
                        <polygon points="0,350 1200,200 1200,400 0,400" fill="url(#orange1)" opacity="0.13" />
                        <!-- Dải hồng nhạt -->
                        <polygon points="0,200 1200,100 1200,180 0,300" fill="url(#pink1)" opacity="0.08" />
                        <defs>
                            <linearGradient id="blue1" x1="0" y1="0" x2="1200" y2="80"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#5ee7df" />
                                <stop offset="1" stop-color="#3a8dde" />
                            </linearGradient>
                            <linearGradient id="blue2" x1="0" y1="120" x2="1200" y2="20"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#3a8dde" />
                                <stop offset="1" stop-color="#0099f7" />
                            </linearGradient>
                            <linearGradient id="orange1" x1="0" y1="350" x2="1200" y2="400"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#ffb347" />
                                <stop offset="1" stop-color="#ffcc33" />
                            </linearGradient>
                            <linearGradient id="pink1" x1="0" y1="200" x2="1200" y2="180"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#ffb6b9" />
                                <stop offset="1" stop-color="#fff" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="banner-content">
                    <div class="content-left banner-text">
                        <h1 class="text-theme" id="preview-title">{{ old('title') ?? ($post->title ?? __('admin.ui.tieu_de')) }}
                        </h1>
                        <h5 class="text-light" id="preview-description">
                            {{ old('description', $post->description ?? __('admin.ui.thong_tin_mo_ta')) }}</h5>
                        <a href="#" class="btn-start" id="preview-btn">
                            {{ old('btn_text', $post->btn_text ?? __('admin.ui.nhan_nut')) }}
                            <i class="fa fa-arrow-right mx-2" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="circle-image-wrapper">
                        <img id="preview-img" />

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
