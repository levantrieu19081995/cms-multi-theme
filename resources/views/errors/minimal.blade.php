<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Trang lỗi')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script> --}}
    <script type="module" src='/assets/js/lottie.min.js'></script>
    <script>
        function docReady(fn) {
            // see if DOM is already available
            if (document.readyState === "complete" || document.readyState === "interactive") {
                // call on next available tick
                setTimeout(fn, 1);
            } else {
                document.addEventListener("DOMContentLoaded", fn);
            }
        }
        docReady(function() {
            lottie.loadAnimation({
                container: document.getElementById('lottie-character'),
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: '/assets/img/not-found.json'
            })
        });
    </script>
</head>

<body class="bg-light">

    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100">

            <div class="col-12 col-sm-6 d-flex justify-content-center mb-4 mb-sm-0">
                {{-- <dotlottie-player src="https://lottie.host/8287d8c4-1271-4f0f-af6b-fd65f2f9f66f/L636UITDm2.lottie"
                    background="transparent" speed="1" style="width: 500px; height: 500px" loop autoplay>
                </dotlottie-player> --}}
                <div id="lottie-character" class="img-fluid object-fit-cover"></div>
            </div>

            <div class="col-12 col-sm-6 d-flex align-items-center">
                <div class='text-center text-sm-start'>
                    <div class="d-flex align-items-center justify-content-center justify-content-sm-start mb-3">
                        <div class="fs-1 fw-bold text-primary border-end pe-3 me-3">
                            @yield('code', '404')
                        </div>
                        <div class="text-uppercase text-muted">
                            @yield('message', 'Không tìm thấy')
                        </div>
                    </div>
                    <p class="text-muted mb-4">
                        Rất tiếc, chúng tôi không thể tìm thấy trang bạn yêu cầu.
                        Vui lòng kiểm tra đường dẫn hoặc quay lại trang trước.
                    </p>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        Về trang chủ
                    </a>
                    <button onclick="history.back()" class="btn btn-secondary ms-2 mt-sm-0">
                        Trở lại
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
