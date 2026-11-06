<div style="position: relative; display: inline-block; margin-right: 20px;"
    onmouseover="this.querySelector('.bubble').style.display='block'"
    onmouseout="this.querySelector('.bubble').style.display='none'">
    <button
        style="
    background-color: #2563eb;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: box-shadow 0.3s ease;
  "
        onmouseover="this.style.boxShadow='0 0 12px 4px rgba(255, 255, 255, 0.8)'"
        onmouseout="this.style.boxShadow='none'">
        👁 Tổng lượt truy cập: {{ $totalSessions }}
    </button>
    <div class="bubble"
        style="
      display: none;
      position: fixed;
      right: 180px;
      bottom: 100px;
      background: #1f4e79;
      padding: 12px;
      border: 1px solid #1f4e79;
      color: white;
      width: auto;
      border-top-left-radius: 4px;
      border-top-right-radius: 4px;
      border-bottom-left-radius: 4px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      z-index: 9999;
    ">
        <p style="margin: 4px 0; color: white;"><strong>Lượt truy cập hôm nay:</strong> {{ $todaySessions }}
        </p>
        <!-- <p style="margin: 4px 0;"><strong>Lượt truy cập hôm qua:</strong> </p> -->
        <p style="margin: 4px 0; color: white;"><strong>Lượt truy cập tháng này:</strong> {{ $monthSessions }}</p>
        <p style="margin: 4px 0; color: white;"><strong>Tổng lượt truy cập:</strong> {{ $totalSessions }}

        </p>
        <!-- Mũi nhọn bên trái -->
        <div
            style="
      position: absolute;
      top: 100%;
      right: -1px;
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-top: 12px solid #1f4e79;
    ">
        </div>
    </div>
</div>
