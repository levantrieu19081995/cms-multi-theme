<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use App\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\CmsBackend\Services\Developer\AccountAdmin;
use Modules\CmsBackend\Support\ModuleUrl;

/** Tab "Tài khoản" của Developer Settings. Chỉ phiên Developer đã mở khóa (middleware cms.developer); logic và nhật ký nằm ở AccountAdmin. */
final class DeveloperAccountsController extends Controller
{
    public function __construct(private AccountAdmin $accounts) {}

    public function password(Request $request): RedirectResponse
    {
        return $this->run(fn () => $this->accounts->resetPassword(
            (int) $request->input('user_id'), (string) $request->input('password', ''), (string) $request->input('password_confirmation', ''), $request->ip()
        ), 'password_saved');
    }

    public function status(Request $request): RedirectResponse
    {
        $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);

        return $this->run(fn () => $this->accounts->setStatus((int) $request->input('user_id'), $active, $request->ip()), $active ? 'status_on' : 'status_off');
    }

    public function editPublished(Request $request): RedirectResponse
    {
        $enabled = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        return $this->run(fn () => $this->accounts->setEditPublished((int) $request->input('user_id'), $enabled, $request->ip()), $enabled ? 'edit_published_on' : 'edit_published_off');
    }

    public function roles(Request $request): RedirectResponse
    {
        $roles = $request->input('roles', []);

        return $this->run(fn () => $this->accounts->syncUserRoles((int) $request->input('user_id'), is_array($roles) ? $roles : [], $request->ip()), 'roles_saved');
    }

    public function rolePermissions(Request $request): RedirectResponse
    {
        $permissions = $request->input('permissions', []);

        return $this->run(fn () => $this->accounts->saveRolePermissions((int) $request->input('role_id'), is_array($permissions) ? $permissions : [], $request->ip()), 'permissions_saved');
    }

    private function run(\Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
            Toastr::success(__('developer_accounts.'.$success), __('admin.message.thanh_cong'));
        } catch (InvalidArgumentException $e) {
            Toastr::toast(__('developer_accounts.error_'.$e->getMessage()), 'error');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
            Toastr::toast(__('developer_accounts.error_permission_unavailable'), 'error');
        }

        return redirect(ModuleUrl::route('cms.dashboard.developer-settings', ['tab' => 'accounts']));
    }
}
