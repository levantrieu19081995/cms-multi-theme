import ApexCharts from 'apexcharts';

/**
 * Charts of "Tổng quan - thống kê" (Truyền thông đa kênh): publications by status (bars), by channel (donut), by creator (area).
 * The page puts the figures in the data-chart attribute of each container: { labels, series, colors, total, i18n }.
 * No request is made from here.
 */
const readConfig = (element) => {
  try {
    return JSON.parse(element.dataset.chart || '{}');
  } catch (error) {
    return {};
  }
};

const foreColor = (element) => getComputedStyle(element).color || '#6b7a99';

const toInt = (value) => Math.round(Number(value) || 0);

const render = (selector, build) => {
  const element = document.querySelector(selector);
  if (!element) {
    return;
  }
  const config = readConfig(element);
  new ApexCharts(element, build(config, foreColor(element))).render();
};

document.addEventListener('DOMContentLoaded', () => {
  // By status: one bar per status, the colour of the status, the number on top, the names in the legend.
  render('#socialStatusChart', (config, color) => ({
    chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit', foreColor: color },
    series: [{ name: config.i18n.quantity, data: config.series }],
    colors: config.colors,
    plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '62%', dataLabels: { position: 'top' } } },
    dataLabels: { enabled: true, offsetY: -20, formatter: toInt, style: { fontSize: '12px', colors: [color] } },
    xaxis: { categories: config.labels, labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }, axisTicks: { show: false } },
    yaxis: {
      min: 0,
      max: Math.max(4, ...config.series.map(toInt)),
      forceNiceScale: true,
      labels: { formatter: toInt },
      title: { text: config.i18n.quantity },
    },
    grid: { strokeDashArray: 4 },
    legend: { show: true, position: 'bottom', fontSize: '12px', markers: { radius: 3 } },
    tooltip: { y: { formatter: toInt } },
  }));

  // By channel: share of each channel in the requests, the total in the middle.
  render('#socialChannelChart', (config, color) => {
    const total = config.series.reduce((sum, value) => sum + toInt(value), 0);

    return {
      chart: { type: 'donut', height: 260, fontFamily: 'inherit', foreColor: color },
      series: total > 0 ? config.series.map(toInt) : [],
      labels: config.labels,
      colors: config.colors,
      noData: { text: config.i18n.noData },
      plotOptions: {
        pie: {
          donut: {
            size: '68%',
            labels: {
              show: true,
              name: { show: true, offsetY: 20, fontSize: '12px' },
              value: { show: true, offsetY: -14, fontSize: '30px', fontWeight: 700, formatter: toInt },
              total: { show: true, showAlways: true, label: config.i18n.center, formatter: () => total },
            },
          },
        },
      },
      dataLabels: { enabled: true, formatter: (value) => Math.round(value) + '%' },
      legend: {
        position: 'bottom',
        fontSize: '12px',
        formatter: (name, options) => {
          const value = toInt(options.w.globals.series[options.seriesIndex]);

          return name + ' (' + (total > 0 ? Math.round((value / total) * 100) : 0) + '%)';
        },
      },
      stroke: { width: 2 },
    };
  });

  // By creator: how many requests each person made.
  render('#socialCreatorChart', (config, color) => ({
    chart: { type: 'area', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: color },
    series: [{ name: config.i18n.quantity, data: config.series }],
    colors: config.colors,
    noData: { text: config.i18n.noData },
    stroke: { curve: 'straight', width: 2 },
    markers: { size: 5, strokeWidth: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
    dataLabels: { enabled: true, formatter: toInt, style: { fontSize: '11px' } },
    xaxis: { categories: config.labels, labels: { trim: true } },
    yaxis: { min: 0, forceNiceScale: true, labels: { formatter: toInt }, title: { text: config.i18n.quantity } },
    grid: { strokeDashArray: 4 },
    legend: { show: false },
    tooltip: { y: { formatter: toInt } },
  }));
});
