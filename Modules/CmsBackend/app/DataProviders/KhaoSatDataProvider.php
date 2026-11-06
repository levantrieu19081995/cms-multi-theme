<?php

namespace Modules\CmsBackend\DataProviders;

use Illuminate\Support\Facades\Schema;
use Modules\CmsFrontend\Models\CauHoi;
use Modules\CmsFrontend\Models\KhaoSatUser;
use Modules\CmsFrontend\Models\KetQua;
use Modules\CmsFrontend\Models\TraLoi;

class KhaoSatDataProvider extends BaseDataProvider
{
    public function __construct()
    {
        parent::__construct(new KhaoSatUser());
    }

    public function createUser($data)
    {
        return KhaoSatUser::create([
            'ksu_ho_ten' => $data['name'],
            'ksu_so_dien_thoai' => $data['phone'],
            'ksu_created_at' => now(),
            'ksu_ket_qua' => $data['ksu_ket_qua'],
            'school_key' => $data['school_key'] ?? null,
            'school_name' => $data['school_name'] ?? null,
        ]);
    }

    public function createKetQua($userId, $ksId, $answers)
    {
        foreach ($answers as $answer) {
            KetQua::create([
                'ks_id' => $ksId,
                'ch_id' => $answer['ch_id'],
                'ksu_id' => $userId,
                'kskq_trl_value' => $answer['kskq_trl_value'],
            ]);
        }
    }
    public function getUsersWithFilter($filters)
    {
        $query = $this->model->newQuery();
        // Tương thích triển khai: trước khi chạy migration khảo sát, cột ksu_deleted_at chưa có; bảng điều khiển vẫn phải mở được.
        if (! Schema::hasColumn($this->model->getTable(), 'ksu_deleted_at')) {
            $query->withoutGlobalScopes();
        }

        if (!empty($filters['ksu_ho_ten'])) {
            $keyword = mb_strtolower($filters['ksu_ho_ten'], 'UTF-8');
            $query->whereRaw('LOWER(ksu_ho_ten) LIKE ?', ['%' . $keyword . '%']);
        }
        if (!empty($filters['ket_qua'])) {
            $keyword = mb_strtolower($filters['ket_qua'], 'UTF-8');
            $query->whereRaw('LOWER(ksu_ket_qua) LIKE ?', ['%' . $keyword . '%']);
        }
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereDate('ksu_created_at', '>=', $filters['from'])
                ->whereDate('ksu_created_at', '<=', $filters['to']);
        }
        if (!empty($filters['school_key'])) {
            $query->where('school_key', $filters['school_key']);
        }
        // Lượt của khảo sát dùng chung không có kết quả nghề nghiệp: biểu đồ cũ chỉ tính lượt có ksu_ket_qua.
        if (!empty($filters['has_result'])) {
            $query->whereNotNull('ksu_ket_qua')->where('ksu_ket_qua', '!=', '');
        }
        if (!empty($filters['year'])) {
            $query->whereYear('ksu_created_at', $filters['year']);
        }

        return $query->orderBy('ksu_created_at', 'desc')->paginate();
    }
    public function getUserSurveyDetail($ksu_id)
    {
        $user = $this->model->findOrFail($ksu_id);

        $ketQuas = KetQua::where('ksu_id', $ksu_id)->get();
        $chiTiet = [];
        foreach ($ketQuas as $kq) {
            $cauHoi = CauHoi::find($kq->ch_id);
            $traLoi = null;
            if ($cauHoi && $cauHoi->ch_type === 'radio') {
                $traLoi = TraLoi::where('ch_id', $kq->ch_id)
                    ->where('trl_value', $kq->kskq_trl_value)
                    ->first();
            }
            $chiTiet[] = [
                'cau_hoi' => $cauHoi ? $cauHoi->ch_noidung : '',
                'kieu' => $cauHoi ? $cauHoi->ch_type : '',
                'tra_loi' => $cauHoi && $cauHoi->ch_type === 'radio'
                    ? ($traLoi ? ($traLoi->trl_value . ': ' . $traLoi->trl_noidung) : $kq->kskq_trl_value)
                    : $kq->kskq_trl_value,
            ];
        }

        return [$user, $chiTiet];
    }
}