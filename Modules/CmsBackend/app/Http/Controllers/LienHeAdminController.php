<?php

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\LienHeService;
use Modules\CmsBackend\Support\ModuleUrl;

/**
 * Quản trị Liên hệ – Góp ý. Quyền kiểm tra ở route (cms.check.permission, đã tính quyền cha lienhe-manager);
 * cổng chức năng cms.feature:contact. Quản trị không nhập liên hệ nên không có create/edit/update.
 */
class LienHeAdminController extends Controller
{
    public function __construct(protected LienHeService $lienHeService) {}

    public function index(Request $request)
    {
        $filter = trim(strip_tags((string) $request->query('filter')));
        $trashed = $request->query('status') === 'trash';
        $dsgopy = $this->lienHeService->paginate($filter, $trashed);

        return view('cmsbackend::screens.pages.lien-he.index', [
            'dsgopy' => $dsgopy,
            'trashed' => $trashed,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function show(int $id)
    {
        return view('cmsbackend::screens.pages.lien-he.show', [
            'gopy' => $this->lienHeService->find($id),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function destroy(int $id)
    {
        $this->lienHeService->destroy($id);
        Toastr::success(__('admin.contact.deleted'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route('cms.dashboard.lien-he.index'));
    }

    public function restore(int $id)
    {
        $this->lienHeService->restore($id);
        Toastr::success(__('admin.contact.restored'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route('cms.dashboard.lien-he.index', ['status' => 'trash']));
    }

    public function purge(int $id)
    {
        if (! $this->lienHeService->purge($id)) {
            Toastr::warning(__('admin.contact.in_trash'), __('admin.message.khong_thanh_cong'));

            return redirect(ModuleUrl::route('cms.dashboard.lien-he.show', $id));
        }
        Toastr::success(__('admin.contact.purged'), __('admin.message.thanh_cong'));

        return redirect(ModuleUrl::route('cms.dashboard.lien-he.index', ['status' => 'trash']));
    }

    /** Chỉ để ẩn/hiện nút; route phía server vẫn kiểm tra quyền. Quyền cha bao trùm quyền con. */
    private function canDelete(): bool
    {
        $user = request()->user();

        return $user !== null
            && ($user->can(PermissionsEnum::LIENHE_DELETE->label()) || $user->can(PermissionsEnum::LIENHE_MANAGER->label()));
    }
}
