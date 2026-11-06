<?php

namespace App\Http\Controllers;

use App\DataProviders\UserDataProvider;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Enums\UserTypesEnum;
use App\Facades\Toastr;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Taxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserTermPermission;
use Illuminate\Support\Str;
use Modules\CmsBackend\DataProviders\UserTermPermissionDataProvider;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\DataProviders\TaxonomyDataProvider;
use App\Enums\Taxonomy as EnumsTaxonomy;
use App\Enums\PermissionsEnum;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

  protected UserDataProvider $userDataProvider;
  protected UserTermPermissionDataProvider $userTermPermissionDataProvider;
  protected TaxonomyDataProvider $taxonomyDataProvider;

  public function __construct(UserDataProvider $userDataProvider, UserTermPermissionDataProvider $userTermPermissionDataProvider, TaxonomyDataProvider $taxonomyDataProvider,)
  {
    $this->userDataProvider = $userDataProvider;
    $this->userTermPermissionDataProvider = $userTermPermissionDataProvider;
    $this->taxonomyDataProvider = $taxonomyDataProvider;


    // bắt buộc đăng nhập (nếu bạn đang dùng auth)
    $this->middleware('auth');

    // Ai có "Quản lý vai trò" là vào được tất cả
    $this->middleware('check.permission:' . PermissionsEnum::ROLE_MANAGER->label())
      ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
  }

  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    // Url query
    $pageSize = $request->pageSize ?? 20;
    $sortBy = $request->sortBy ?? 'name';
    $order = $request->order ?? 'asc';
    // UserStatusEnum App\Enums\UserStatusEnum;
    $filterUserStatuses = $request->getBase64Param('userStatuses') ?? [];
    // UserType App\Enums\UserTypesEnum;
    $filterUserTypes = $request->getBase64Param('userTypes') ?? [];
    $filterRoleIds = $request->getBase64Param('roles') ?? [];
    // dd($roleIds);
    $filter = trim(strip_tags($request->query('filter')));

    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $users */
    $users = $this->userDataProvider->paginate($filterUserTypes, $filterUserStatuses, $filterRoleIds, $filter, $sortBy, $order, $pageSize);
    $users->appends($request->all());

    // Load dependencies
    $filterRoleIds = collect($filterRoleIds);
    $filterUserStatuses = collect($filterUserStatuses);
    $filterUserTypes = collect($filterUserTypes);

    $roles = Role::all();
    foreach ($roles as $role) {
      $role->selected = $filterRoleIds->some(function ($id) use ($role) {
        return $id == $role->id;
      });
    }
    $userStatuses = [];
    foreach (UserStatusEnum::cases() as $userStatus) {
      $userStatuses[] = [
        'enum' => $userStatus,
        'selected' => $filterUserStatuses->some(function ($status) use ($userStatus) {
          return $status == $userStatus->value;
        })
      ];
    }
    $userTypes = [];
    foreach (UserStatusEnum::cases() as $userType) {
      $userTypes[] = [
        'enum' => $userType,
        'selected' => $filterUserTypes->some(function ($type) use ($userType) {
          return $type == $userType->value;
        })
      ];
    }
    return view('pages.user.user-list', compact(['users', 'roles', 'userStatuses', 'userTypes']));
  }

  public function create()
  {
    $categories = Taxonomy::where('taxonomy', 'category')->with('term')->get();
    $user = new User;
    $roles = Role::all();
    $terms = [];
    foreach ($categories as $cate) {
      $terms[] = [
        'id' => $cate->term_id,
        'name' => $cate->term->name,
        'canCreated' => 0
      ];
    }

    // cây chuyên mục
    $categoryParenChild = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
    // create thì chưa có quyền chuyên mục => rỗng
    $userTermPermission = [];
    $userTermPermissionMap = [];

    return view('pages.user.user-edit', compact([
      'user',
      'roles',
      'terms',
      'categoryParenChild',
      'userTermPermission',
      'userTermPermissionMap',
    ]));
  }





  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(CreateUserRequest $request)
  {
    $user = new User;
    $user->fill($request->all());
    $user->password = Hash::make($request->password);
    $user->type = UserTypesEnum::USER;
    $user->status = 1;  // hoặc giá trị mặc định bạn muốn
    // dd("User->>> ", $user);
    $user->save();

    if ($request->roles) {
      $roleIds = [];
      foreach ($request->roles as $roleIdEncrypted) {
        $roleIds[] = Crypt::decryptString($roleIdEncrypted);
      }
      $syncRoles = Role::whereIn('id', $roleIds)->get();
      $user->syncRoles($syncRoles);
    }

    // if ($request->terms) {
    //   $this->userTermPermissionDataProvider->addUserTermPermission($user->id, $request->terms);
    // } 
    if ($request->category_ids) {
      $this->userTermPermissionDataProvider->addUserTermPermission($user->id, $request->category_ids);
    }

    Toastr::success(__('Thêm mới thành công'));

    event(new Registered($user));

    // return redirect()->route('users.edit', $user);
    return redirect()->route('users.index', $user);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    $user = User::findOrFail($id);
    $roles = Role::all();
    foreach ($roles as $role) {
      $role->assigned = $user->roles->some(function ($userRole) use ($role) {
        return $userRole->id === $role->id;
      });
    }


    $userTermPermission = $this->userTermPermissionDataProvider->getTermIdsByUserId($user->id);
    // chuyển thành mảng dạng key => true để tra nhanh
    $userTermPermissionMap = array_flip($userTermPermission);



    // // Lấy danh sách các chuyên mục
    // $categories = Taxonomy::where('taxonomy', 'category')->with('term')->get(); 
    // $terms = [];
    // dd($categories);
    // foreach ($categories as $cate) {
    //   $terms[] = [
    //     'id' => $cate->term_id,
    //     'name' => $cate->term->name,
    //     'canCreated' => isset($userTermPermissionMap[$cate->term_id])
    //   ];
    // }
    // dd($terms);
    // return view('pages.user.user-edit', compact(['user', 'roles', 'terms']));

    //Lấy danh sách 10 chuyên mục được sử dụng nhiều
    $categoryParenChild = $this->taxonomyDataProvider->getCategoryHierarchy(0, EnumsTaxonomy::CATEGORY->value);
    // dd($categoryParenChild->toArray());
    return view('pages.user.user-edit', compact([
      'user',
      'roles',
      'categoryParenChild',
      'userTermPermission',
      'userTermPermissionMap',
    ]));
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(UpdateUserRequest $request, $id)
  {

    $user = User::findOrFail($id);

    // Kiểm tra mật khẩu mới và mật khẩu cũ
    if ($request->filled('password') && Hash::check($request->password, $user->password)) {
      // Nếu mật khẩu mới trùng với mật khẩu cũ, thông báo lỗi và dừng cập nhật
      // Toastr::error(__('Mật khẩu mới không được trùng với mật khẩu cũ'));
      // Toastr::success(__('Mật khẩu mới không được trùng với mật khẩu cũ'));
      return redirect()->back()->withErrors(['password' => 'Mật khẩu mới không được trùng với mật khẩu cũ']);
    }

    $user->fill($request->only(['name', 'email', 'status', 'username']));

    $passwordChanged = false;

    // //đoạn này lỗi vì luôn lấy từ user rồi nó hash lại lần nữa nên khi cập nhật auto sai mk
    // if ($user->password) {
    //   $user->password = Hash::make($request->password);
    // }

    if ($request->filled('password')) {
      $user->password = Hash::make($request->password);
      $passwordChanged = true;
    }

    // $user->type = UserTypesEnum::USER; 
    if (!isset($request->status)) {
      $user->status = 0;
    }



    $user->save();

    //  LOGOUT TOÀN BỘ THIẾT BỊ SAU KHI ĐỔI MẬT KHẨU 
    if ($passwordChanged) {
      DB::table('sessions')
        ->where('user_id', $user->id)
        ->delete();
    }

    if ($request->roles) {
      $roleIds = [];
      foreach ($request->roles as $roleIdEncrypted) {
        $roleIds[] = Crypt::decryptString($roleIdEncrypted);
      }
      $syncRoles = Role::whereIn('id', $roleIds)->get();
      $user->syncRoles($syncRoles);
    }
    // if ($request->terms) {
    //   $this->userTermPermissionDataProvider->addUserTermPermission($user->id, $request->terms);
    //   // dd($request->terms);
    // }
    if ($request->category_ids) {
      $this->userTermPermissionDataProvider->addUserTermPermission($user->id, $request->category_ids);
    }


    Toastr::success(__('Cập nhật thành công'));

    // return redirect()->route('users.edit', $user); 
    return redirect()->route('users.index', $user);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    // dd('xoa usser');
    $user = User::findOrFail($id);

    if ($user->id == Auth::user()->id) {
      // return response()->json([
      //   'status' => 0,
      //   'message' => 'Không thể xóa tài khoản đang đăng nhập'
      // ], 401);
      Toastr::success(__('Không thể xóa tài khoản đang đăng nhập'));
      return redirect()->route('users.index');
    }

    if ($user->id != Auth::user()->id) {
      $user->delete();
    }
    // return response()->json([
    //   'status' => 1,
    //   'message' => 'Xóa thành công'
    // ]);
    Toastr::success(__('Xóa thành công !'));
    return redirect()->route('users.index');
  }
}
