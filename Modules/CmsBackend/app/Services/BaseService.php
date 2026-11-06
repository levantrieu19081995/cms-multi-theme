<?php

namespace Modules\CmsBackend\Services;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class BaseService
{
    protected $dataProvider;

    public function __construct($dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Lấy danh sách bản ghi có phân trang.
     */
    public function paginate(
        string $filter = '',
        array $searchableColumns = [],
        array $conditions = [],
        array $withMethods = [],
        string $sortBy = 'id',
        string $order = 'desc',
        int $pageSize = 10,
        array $withCount = []
    ) {
        try {
            return $this->dataProvider->paginate($filter, $searchableColumns, $conditions, $withMethods, $sortBy, $order, $pageSize, $withCount);
        } catch (Exception $e) {
            throw new Exception('Không thể lấy danh sách phân trang'.$e->getMessage());
        }
    }

    /**
     * Lấy danh sách liên quan.
     */
    public function getDanhSachLienQuan(array $customModels = [], array $expectedColumns = [])
    {
        try {
            return $this->dataProvider->getDanhSachLienQuan($customModels, $expectedColumns);
        } catch (Exception $e) {
            throw new Exception('Không thể lấy dữ liệu từ bảng liên quan'.$e->getMessage());
        }
    }

    /**
     * Lấy danh sách tên cột. Bao gồm toàn bộ thuộc tính của cột trong bảng.
     */
    public function getTableColumns()
    {
        try {
            return $this->dataProvider->getColumns();
        } catch (Exception $e) {
            throw new Exception('Không thể lấy tên cột'.$e->getMessage());
        }
    }

    /**
     * Lấy quan hệ các bảng.
     */
    public function getModelRelations()
    {
        try {
            return $this->dataProvider->getTableRelations();
        } catch (Exception $e) {
            throw new Exception('Không thể lấy quan hệ các bảng'.$e->getMessage());
        }
    }

    public function findByIdWithRelations($id, $relations = [])
    {
        try {
            return $this->dataProvider->findByIdWithRelations($id, $relations);
        } catch (Exception $e) {
            throw new Exception('Không thể lấy dữ liệu'.$e->getMessage());
        } catch (ModelNotFoundException $e) {
            throw new Exception('Không tìm thấy dữ liệu'.$e->getMessage());
        }
    }

    /**
     * Lấy tất cả bản ghi.
     */
    public function all(array $columns = ['*'])
    {
        try {
            return $this->dataProvider->all($columns);
        } catch (Exception $e) {
            throw new Exception('Không thể lấy được dữ liệu'.$e->getMessage());
        }
    }

    /**
     * Tạo mới bản ghi.
     */
    public function create(array $data)
    {
        try {
            $record = $this->dataProvider->create($data);

            // event(new DmHanggiaypheplaixeEvent('create', $record)); // Sự kiện khi tạo mới
            return $record;
        } catch (QueryException $e) {
            // Kiểm tra nếu lỗi là do ràng buộc khóa ngoại (FOREIGN KEY)
            if ($this->isForeignKeyError($e)) {
                throw new Exception('Không thể thêm mới dữ liệu: Giá trị khóa ngoại không hợp lệ.');
            }
            throw new Exception('Không thể thêm mới dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Cập nhật bản ghi theo ID.
     *
     * @param  int  $id:  ID của bản ghi cần cập nhật
     * @param  array  $data:  Dữ liệu cần cập nhật
     * @param  string  $primaryKey:  Tên cột khóa chính (mặc định là 'id') | nếu trong bảng không có cột 'id' thì truyền vào tên cột khác
     */
    public function update(?int $id, array $data, ?string $primaryKey = null)
    {
        try {
            $record = $this->dataProvider->update($id, $data, $primaryKey);

            // event(new DmHanggiaypheplaixeEvent('update', $record)); // Sự kiện khi cập nhật
            return $record;
        } catch (QueryException $e) {
            if ($this->isForeignKeyError($e)) {
                throw new Exception('Không thể cập nhật dữ liệu: Giá trị khóa ngoại không hợp lệ.');
            }
            throw new Exception('Không thể cập nhật dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Tìm một bản ghi theo ID.
     *
     * @param  int  $id:  ID của bản ghi cần tìm
     * @param  string  $primaryKey:  Tên cột khóa chính (mặc định là 'id') | nếu trong bảng không có cột 'id' thì truyền vào tên cột khác
     */
    public function find(int $id, ?string $primaryKey = null, array $relations = [])
    {
        try {
            $result = $this->dataProvider->find($id, $primaryKey, $relations);

            if (! $result) {
                throw new Exception("Không tìm thấy dữ liệu với $primaryKey: $id");
            }

            return $result;
        } catch (ModelNotFoundException $e) {
            throw new Exception("Không tìm thấy dữ liệu với $primaryKey: $id");
        } catch (Exception $e) {
            throw new Exception('Lỗi khi tìm dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Tìm tất cả bảng ghi theo ID trong bảng tham chiếu
     */
    public function findAllInReferenceTable(int $id, ?string $primaryKey = null, array $relations = [])
    {
        try {
            $result = $this->dataProvider->findAllInReferenceTable($id, $primaryKey, $relations);

            if (! $result) {
                throw new Exception("Không tìm thấy dữ liệu với $primaryKey: $id");
            }

            return $result;
        } catch (ModelNotFoundException $e) {
            throw new Exception("Không tìm thấy dữ liệu với $primaryKey: $id");
        } catch (Exception $e) {
            throw new Exception('Lỗi khi tìm dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Xóa (mềm) bản ghi.
     */
    public function destroy(int $id, array $permissionsBase = [], ?string $primaryKey = null, array $relations = [])
    {
        $record = $this->dataProvider->find($id, $primaryKey, $relations);

        if (! $record) {
            throw new ModelNotFoundException('Không tìm thấy bản ghi.');
        }
        if ($permissionsBase) {
            if (! $permissionsBase['canDelete']) {
                throw new AuthorizationException('Bạn không có quyền xóa bản ghi này.');
            }
        }

        if (! $this->dataProvider->destroy($id, true)) {
            throw new Exception('Lỗi khi xóa bản ghi.', 500);
        }

        return true;
    }

    public function delete(int $id)
    {
        try {
            $record = $this->dataProvider->delete($id);

            // Kiểm tra nếu thực sự có bản ghi bị xóa
            if (! $record) {
                throw new ModelNotFoundException('Không tìm thấy dữ liệu.');
            }

            return ['status' => true, 'message' => 'Xóa thành công!', 'data' => $record];
        } catch (ModelNotFoundException $e) {
            throw new Exception('Không tìm thấy dữ liệu: '.$e->getMessage(), 404);
        } catch (Exception $e) {
            throw new Exception('Lỗi khi xóa: '.$e->getMessage(), 400);
        }
    }

    /**
     * Kiểm tra sự tồn tại của bản ghi.
     */
    public function exists(int $id): bool
    {
        try {
            return $this->dataProvider->exists($id);
        } catch (Exception $e) {
            throw new Exception('Không tìm thấy dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Đếm tổng số bản ghi.
     */
    public function count(): int
    {
        try {
            return $this->dataProvider->count();
        } catch (Exception $e) {
            throw new Exception('Không thể đếm tổng số dữ liệu: '.$e->getMessage());
        }
    }

    /**
     * Hàm kiểm tra lỗi khóa ngoại (FOREIGN KEY)
     */
    private function isForeignKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'foreign key constraint fails');
    }
}
