import ApexCharts from 'apexcharts';

document.addEventListener('DOMContentLoaded', () => {
  const chartEl = document.querySelector('#postChart');
  const trendEl = document.querySelector('#postTrendChart');
  if (!chartEl || !trendEl) return;

  const parse = (value, fallback) => {
    try {
      return JSON.parse(value || '');
    } catch (_) {
      return fallback;
    }
  };

  const months = parse(chartEl.dataset.categories, []);
  const series = parse(chartEl.dataset.series, []);
  const categoryStatus = parse(chartEl.dataset.categoryStatus, {});
  const monthStatus = parse(chartEl.dataset.monthStatus, {});
  const number = value => Number(value) || 0;
  const formatNumber = value => new Intl.NumberFormat('vi-VN').format(number(value));
  const monthLabel = value => {
    const [month, year] = String(value).split('-');
    return year ? `T${Number(month)}/${year}` : String(value);
  };
  const trendMonths = months.slice(-12);
  const trendStartIndex = Math.max(months.length - trendMonths.length, 0);

  const categoryRows = series
    .map(item => ({
      key: item.name,
      label: item.name,
      total: (item.data || []).reduce((sum, value) => sum + number(value), 0),
      trend: trendMonths.map((month, index) => ({ x: monthLabel(month), y: number(item.data?.[trendStartIndex + index]) })),
      status: categoryStatus[item.name] || {}
    }))
    .sort((a, b) => b.total - a.total || a.label.localeCompare(b.label, 'vi'));

  const overallTrend = trendMonths.map(month => ({ x: monthLabel(month), y: number(monthStatus[month]?.total) }));
  const monthRows = trendMonths
    .map(month => ({
      key: month,
      label: monthLabel(month),
      total: number(monthStatus[month]?.total),
      trend: overallTrend,
      status: monthStatus[month] || {}
    }))
    .sort((a, b) => b.total - a.total);

  const detailTitle = document.querySelector('#postDetailTitle');
  const detailKind = document.querySelector('#postDetailKind');
  const detailTotal = document.querySelector('#postDetailTotal');
  const detailPublished = document.querySelector('#postDetailPublished');
  const detailPending = document.querySelector('#postDetailPending');
  const detailDraft = document.querySelector('#postDetailDraft');
  const trendTitle = document.querySelector('#postTrendTitle');
  const tabButtons = [...document.querySelectorAll('[data-post-view]')];

  let mode = 'category';
  let selectedIndex = 0;
  let barChart;

  const trendChart = new ApexCharts(trendEl, {
    series: [{ name: window.cmsAdminTranslations.posts, data: [] }],
    chart: { type: 'area', height: 190, toolbar: { show: false }, animations: { speed: 320 } },
    colors: ['#2f80ed'],
    stroke: { width: 2.5, curve: 'smooth' },
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: .04, stops: [0, 95, 100] }
    },
    dataLabels: { enabled: false },
    markers: { size: 4, colors: ['#2f80ed'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
    grid: { borderColor: '#dfe9e4', strokeDashArray: 3, padding: { left: 4, right: 6 } },
    xaxis: { type: 'category', labels: { style: { colors: '#6d7890', fontSize: '11px' } }, axisBorder: { color: '#d2ddd8' }, axisTicks: { color: '#d2ddd8' } },
    yaxis: { min: 0, forceNiceScale: true, labels: { formatter: value => Math.round(value), style: { colors: '#6d7890' } } },
    tooltip: { y: { formatter: value => `${formatNumber(value)} ${window.cmsAdminTranslations.posts_unit}` } },
    legend: { show: false }
  });

  trendChart.render();

  const currentRows = () => mode === 'category' ? categoryRows : monthRows;

  const updateDetail = row => {
    if (!row) {
      detailTitle.textContent = window.cmsAdminTranslations.no_data_yet;
      detailTotal.textContent = '0';
      detailPublished.textContent = '0';
      detailPending.textContent = '0';
      detailDraft.textContent = '0';
      trendChart.updateSeries([{ name: window.cmsAdminTranslations.posts, data: [] }]);
      return;
    }

    detailKind.textContent = mode === 'category' ? window.cmsAdminTranslations.category_lower : window.cmsAdminTranslations.month_lower;
    detailTitle.textContent = row.label;
    detailTotal.textContent = formatNumber(row.total);
    detailPublished.textContent = formatNumber(row.status.published);
    detailPending.textContent = formatNumber(row.status.pending_approval);
    detailDraft.textContent = formatNumber(row.status.draft);
    trendTitle.textContent = mode === 'category' ? window.cmsAdminTranslations.trend_monthly : window.cmsAdminTranslations.trend_total_monthly;
    trendChart.updateSeries([{ name: window.cmsAdminTranslations.posts, data: row.trend }], true);
  };

  const updateBars = () => {
    if (!barChart) return;
    barChart.updateOptions({
      colors: currentRows().map((_, index) => index === selectedIndex ? '#2563eb' : '#8dbcf8')
    }, false, true);
  };

  const renderBars = () => {
    const rows = currentRows();
    selectedIndex = Math.min(selectedIndex, Math.max(rows.length - 1, 0));
    const height = Math.max(390, rows.length * 58 + 70);
    // Leave headroom past the longest bar so its value label stays inside the plot.
    const maxTotal = Math.max(0, ...rows.map(row => row.total));
    const tickAmount = 4;
    const unit = 10 ** Math.max(Math.floor(Math.log10(maxTotal * 1.2 || 1)) - 1, 0) * tickAmount;
    const axisMax = Math.max(tickAmount, Math.ceil((maxTotal * 1.2) / unit) * unit);

    const options = {
      series: [{ name: window.cmsAdminTranslations.posts, data: rows.map(row => row.total) }],
      chart: {
        type: 'bar',
        height,
        toolbar: { show: false },
        animations: { speed: 280 },
        events: {
          dataPointSelection: (_, __, config) => {
            selectedIndex = config.dataPointIndex;
            updateBars();
            updateDetail(currentRows()[selectedIndex]);
          }
        }
      },
      colors: rows.map((_, index) => index === selectedIndex ? '#2563eb' : '#8dbcf8'),
      plotOptions: {
        bar: {
          horizontal: true,
          distributed: true,
          borderRadius: 5,
          borderRadiusApplication: 'end',
          barHeight: '48%',
          dataLabels: { position: 'top' }
        }
      },
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        offsetX: 10,
        formatter: value => formatNumber(value),
        style: { colors: ['#17233c'], fontSize: '13px', fontWeight: 700 },
        background: { enabled: false }
      },
      grid: { borderColor: '#dce5f0', strokeDashArray: 3, padding: { right: 42, left: 4 } },
      xaxis: {
        categories: rows.map(row => row.label),
        min: 0,
        max: axisMax,
        tickAmount,
        labels: { formatter: value => formatNumber(Math.round(value)), style: { colors: '#738097', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          maxWidth: 210,
          style: { colors: '#273550', fontSize: '13px', fontWeight: 600 }
        }
      },
      tooltip: { y: { formatter: value => `${formatNumber(value)} ${window.cmsAdminTranslations.posts_unit}` } },
      legend: { show: false },
      states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } }
    };

    if (barChart) barChart.destroy();
    barChart = new ApexCharts(chartEl, options);
    barChart.render();
    updateDetail(rows[selectedIndex]);
  };

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      mode = button.dataset.postView;
      selectedIndex = 0;
      tabButtons.forEach(item => {
        const active = item === button;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      renderBars();
    });
  });

  renderBars();
});
