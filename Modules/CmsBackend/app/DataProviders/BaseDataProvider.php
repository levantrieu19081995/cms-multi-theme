<?php

namespace Modules\CmsBackend\DataProviders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\Eloquent\Model;

class BaseDataProvider
{
    protected Model $model; // Model sẽ được truyền vào từ Service con

    protected $moduleName;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy danh sách dữ liệu có phân trang.
     * @param string $filter: từ khóa tìm kiếm
     * @param array $searchableColumns: chỉ định cột cần tìm | hoặc bỏ qua nếu muốn tìm tất cả cột
     * @param array $conditions Các điều kiện lọc dữ liệu (['column' => 'value'])
     * @param array $withMethods Các phương thức quan hệ được khai báo trong Model | Mặc định sẽ lấy hết tất cả quan hệ
     * @param string $sortBy: sắp xếp theo thứ tự
     * @param string $order: chiều sắp xếp
     * @param int $pageSize: số dữ liệu trên mỗi trang
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
    ): LengthAwarePaginator {
        $query = $this->model->query();

        // Lọc các dữ liệu chưa bị xóa mềm
        if (Schema::hasColumn($this->model->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (!empty($withCount)) {
            foreach ($withCount as $relation) {
                $query->withCount([
                    $relation => function ($q) use ($relation) {
                        $relationInstance = $this->model->$relation();
                        $relatedModel = $relationInstance->getRelated();
                        $relationTable = $relatedModel->getTable();

                        // Kiểm tra bảng quan hệ có cột deleted_at hay không
                        if (Schema::hasColumn($relationTable, 'deleted_at')) {
                            $q->whereNull("$relationTable.deleted_at");
                        }
                    }
                ]);
            }
        }


        if (empty($withMethods)) {
            $relations = $this->getTableRelations() ?? [];
            $withMethods = !empty($relations) ? array_column($relations, 'method') : [];
        }

        if (!empty($withMethods)) {
            $query->with($withMethods);
        }

        // Áp dụng các điều kiện lọc (nếu có)
        foreach ($conditions as $column => $value) {
            if (Schema::hasColumn($this->model->getTable(), $column) && $value !== null) {
                $query->where($column, $value);
            }
        }

        // Nếu `searchableColumns` rỗng, tự động lấy tất cả các cột từ bảng
        if (empty($searchableColumns)) {
            $searchableColumns = Schema::getColumnListing($this->model->getTable());

            // Loại bỏ các cột không cần thiết
            $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
            $searchableColumns = array_diff($searchableColumns, $excludedColumns);
        }

        // Lọc dữ liệu theo `filter` trên tất cả các cột hợp lệ
        if (!empty($filter) && !empty($searchableColumns)) {
            $query->where(function ($q) use ($searchableColumns, $filter) {
                foreach ($searchableColumns as $column) {
                    if (str_contains($column, '.')) {
                        // Trường liên kết (relation.column)
                        [$relation, $field] = explode('.', $column);
                        $q->orWhereHas($relation, function ($subQuery) use ($field, $filter) {
                            $subQuery->where($field, 'like', "%$filter%");
                        });
                    } else {
                        // Trường trực tiếp trong bảng
                        $q->orWhere($column, 'like', "%$filter%");
                    }
                }
            });
        }


        return $query->orderBy($sortBy, $order)->paginate($pageSize);
    }

    /**
     * Kiểm tra sự tồn tại của file JSON.
     *
     * @return bool
     */
    public function fileExists($filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * Lấy dữ liệu từ file JSON.
     *
     * @return array|null
     */
    public function getColumns(): ?array
    {
        return $this->getTableColumns($this->model->getTable());
    }

    public function all(array $columns = ['*'])
    {
        $query = $this->model->query();

        if (Schema::hasColumn($this->model->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        // Lấy danh sách quan hệ từ JSON hoặc Model
        $relations = $this->getTableRelations();
        $relationMethods = !empty($relations) ? array_column($relations, 'method') : [];

        if (!empty($relationMethods)) {
            $query->with($relationMethods);
        }

        return $query->get($columns);
    }

    public function find($value, $primaryKey, array $relations = [])
    {
        $primaryKey = $primaryKey ?? $this->model->getKeyName();

        // Kiểm tra xem cột có tồn tại trong bảng không
        if (!Schema::hasColumn($this->model->getTable(), $primaryKey)) {
            throw new \Exception("Column '$primaryKey' không tồn tại trong bảng " . $this->model->getTable());
        }

        // Kiểm tra nếu có soft delete thì lọc dữ liệu chưa bị xóa mềm
        $query = $this->model->query();
        if (Schema::hasColumn($this->model->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->where($primaryKey, $value)->first();
    }

    // Tìm trong bảng tham chiếu
    public function findAllInReferenceTable($value, $primaryKey, array $relations = [])
    {
        $primaryKey = $primaryKey ?? $this->model->getKeyName();

        if (!Schema::hasColumn($this->model->getTable(), $primaryKey)) {
            throw new \Exception("Column '$primaryKey' không tồn tại trong bảng " . $this->model->getTable());
        }

        $query = $this->model->query();
        // Lọc data chưa bị xóa mềm trong bảng chính!
        if (Schema::hasColumn($this->model->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->where($primaryKey, $value)->get();
    }


    public function create(array $data)
    {

        return $this->model->create($data);
    }

    public function update(int $id, array $data, $primaryKey)
    {

        // Nếu không truyền $primaryKey, lấy khóa chính của model
        $primaryKey = $primaryKey ?? $this->model->getKeyName();

        // Tìm model theo primaryKey
        $model = $this->model->where($primaryKey, $id)->first();

        if (!$model) {
            throw new Exception("Không tìm thấy bản ghi với $primaryKey: $id");
        }

        // Cập nhật dữ liệu
        $model->update($data);
        return $model;
    }


    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Xóa mềm (cập nhật cột deleted_at)
     */
    public function destroy(int $id, $completelyErase = false): bool
    {
        return DB::transaction(function () use ($id, $completelyErase) {
            $record = $this->model->find($id);

            if (!$record) {
                return false; // Không tìm thấy bản ghi
            }
            if ($completelyErase) {
                return $record->delete();
            }
            // Xóa mềm
            $record->deleted_at = Carbon::now();
            return $record->save();
        });
    }

    public function destroyWithRelations(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $record = $this->model->find($id);

            if (!$record) {
                return false; // Không tìm thấy bản ghi
            }

            // Xóa mềm
            $record->deleted_at = Carbon::now();
            $record->save();

            // Xóa mềm các bản ghi liên quan
            $relations = $this->getTableRelations();
            foreach ($relations as $relation) {
                $relationMethod = $relation['method'];

                if (method_exists($record, $relationMethod)) {
                    $relatedRecords = $record->{$relationMethod}()->get();
                    foreach ($relatedRecords as $relatedRecord) {
                        $relatedRecord->deleted_at = Carbon::now();
                        $relatedRecord->save();
                    }
                }
            }

            return true;
        });
    }


    /**
     * Hàm xóa bản ghi chung cho mọi bảng. (Xóa vĩnh viễn)
     */
    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();

            $record = $this->model->find($id);
            if (!$record) {
                throw new Exception("Bản ghi với ID $id không tồn tại.");
            }

            if (!$this->canDelete($record)) {
                throw new Exception("Không thể xóa bản ghi do có dữ liệu liên quan.");
            }

            $record->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Để Service xử lý lỗi
        }
    }

    /**
     * Tìm kiếm theo id và các quan hệ đính kèm
     */
    public function findByIdWithRelations(int $id, array $relations = [])
    {
        $query = $this->model->newQuery();

        // Get all direct relations dynamically
        $directRelations = $this->getTableRelations();
        $relationMethods = !empty($directRelations) ? array_column($directRelations, 'method') : [];

        // Automatically detect and load nested relations
        $nestedRelations = [];
        foreach ($relationMethods as $relation) {
            $relatedModel = $this->model->{$relation}()->getRelated();
            if (method_exists($relatedModel, 'getTableRelations')) {
                $subRelations = array_column($relatedModel->getTableRelations(), 'method');
                foreach ($subRelations as $subRelation) {
                    $nestedRelations[] = "{$relation}.{$subRelation}";
                }
            }
        }

        // Merge relations: automatic + user-defined
        $allRelations = array_merge($relationMethods, $nestedRelations, $relations);

        // Exclude soft-deleted data if `deleted_at` exists
        if (Schema::hasColumn($this->model->getTable(), 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        // Apply eager loading dynamically
        if (!empty($allRelations)) {
            $query->with($allRelations);
        }

        return $query->findOrFail($id);
    }

    /**
     * Kiểm tra xem bản ghi có dữ liệu liên quan không trước khi xóa.
     */
    public function canDelete($record)
    {
        $relations = method_exists($this->model, 'getTableRelations')
            ? $this->model->getTableRelations()
            : [];

        if (empty($relations)) {
            return true; // Không có quan hệ, có thể xóa
        }

        foreach ($relations as $relation) {
            $relationMethod = $relation['method'];

            // Nếu có quan hệ `hasMany` hoặc `hasOne`, kiểm tra xem có dữ liệu liên quan không
            if (in_array($relation['type'], ['hasMany', 'hasOne'])) {
                if (method_exists($record, $relationMethod) && $record->{$relationMethod}()->exists()) {
                    return false;
                }
            }

            // Nếu có quan hệ `belongsTo`, kiểm tra khóa ngoại có còn tồn tại trong bảng cha không
            if ($relation['type'] === 'belongsTo') {

                $foreignKey = $relation['foreign_key'];

                if (!empty($record->$foreignKey) && $this->model::where('id', $record->$foreignKey)->exists()) {
                    return false;
                }
            }
        }

        return true; // Không có quan hệ, có thể xóa
    }

    /**
     * Lấy thông tin quan hệ giữa các bảng
     */
    public function getTableRelations()
    {
        $relations = $this->getRelationsFromModel($this->model);
        return $relations;
    }

    /**
     * Lấy danh sách quan hệ từ model
     * @param mixed $model
     * @return array{foreign_key: mixed|string|null, method: string, related_table: mixed, type: string[]}
     */
    public function getRelationsFromModel($model)
    {
        $relations = [];

        // Kiểm tra nếu `$model` hợp lệ trước khi thao tác
        if (!is_object($model) || !method_exists($model, 'getConnection')) {
            return $relations; // Trả về mảng rỗng nếu model không hợp lệ
        }

        // Lấy danh sách các phương thức có thể là quan hệ
        $methods = get_class_methods($model);

        foreach ($methods as $method) {
            if (in_array($method, ['getConnectionResolver', 'getQueueableRelations', 'boot', 'bootIfNotBooted'])) {
                continue; // Bỏ qua các phương thức hệ thống không cần thiết
            }

            try {
                $reflectionMethod = new \ReflectionMethod($model, $method);

                if ($reflectionMethod->isPublic() && !$reflectionMethod->isStatic() && $reflectionMethod->getNumberOfParameters() === 0) {
                    $newInstance = new $model();

                    if (!method_exists($newInstance, $method)) {
                        continue;
                    }

                    $returnType = $reflectionMethod->invoke($newInstance);

                    if ($returnType instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        // Xác định loại quan hệ
                        $relationType = match (true) {
                            $returnType instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo => 'belongsTo',
                            $returnType instanceof \Illuminate\Database\Eloquent\Relations\HasMany => 'hasMany',
                            $returnType instanceof \Illuminate\Database\Eloquent\Relations\HasOne => 'hasOne',
                            $returnType instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany => 'belongsToMany', // 🔥 Thêm belongsToMany
                            default => 'unknown',
                        };

                        $relations[] = [
                            'method' => $method,
                            'related_table' => $returnType->getRelated()->getTable(),
                            'foreign_key' => method_exists($returnType, 'getForeignKeyName')
                                ? $returnType->getForeignKeyName()
                                : null,
                            'type' => $relationType, // Đảm bảo có `type`
                        ];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relations; // Đảm bảo trả về một mảng hợp lệ
    }


    /**
     * Hàm chức năng lấy ds các bảng liên quan
     * @param array $customModels: dùng cho trường hợp bảng nằm ở ngoài module | địa chỉ khác
     * @param array $expectedColumns: dùng để lấy các cột cụ thể trong bảng liên quan
     * @return array
     */
    public function getDanhSachLienQuan(array $customModels = [], array $expectedColumns = [])
    {
        $relations = $this->getTableRelations();
        $relatedData = [];

        foreach ($relations as $relation) {
            $relatedTableKey = $relation['related_table'];

            // Check if the related table name is valid
            if (!isset($relation['related_table']) || !is_string($relation['related_table'])) {
                continue;
            }

            // Convert table name to Model Class (PascalCase)
            $relatedTable = Str::studly(Str::singular($relation['related_table']));

            // Check for custom model mapping first
            $relatedModelClass = $customModels[$relatedTableKey] ?? null;

            // If no custom model, try to find one automatically
            if (!$relatedModelClass) {
                $potentialModels = [
                    "Modules\\{$this->moduleName}\\Models\\{$relatedTable}",
                    "App\\Models\\{$relatedTable}"
                ];

                foreach ($potentialModels as $modelClass) {
                    if (class_exists($modelClass)) {
                        $relatedModelClass = $modelClass;
                        break;
                    }
                }
            }

            // If no valid model is found, skip
            if (!$relatedModelClass || !class_exists($relatedModelClass)) {
                continue;
            }

            // Get instance of the related model
            $relatedModel = new $relatedModelClass();

            // Ensure expected columns are valid
            $columnsToSelect = isset($expectedColumns[$relatedTableKey]) && is_array($expectedColumns[$relatedTableKey])
                ? $expectedColumns[$relatedTableKey]
                : ['*'];

            // Query and store related data
            $query = $relatedModel->newQuery();

            if (Schema::hasColumn($relatedModel->getTable(), 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $relatedData[$relatedTableKey] = $query->get($columnsToSelect);
        }

        return $relatedData;
    }


    protected function getTableColumns($tableName)
    {
        // Kiểm tra sự tồn tại của bảng
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        // Truy vấn thông tin cột từ information_schema.columns
        $columns = DB::select("
        SELECT 
            c.column_name AS name,
            c.data_type AS type,
            c.is_nullable AS nullable,
            c.column_default AS default_value,
            c.column_comment AS comment,
            c.character_maximum_length AS max_length,
            c.extra AS extra_info,
            IF(s.index_name IS NOT NULL, TRUE, FALSE) AS is_unique
        FROM information_schema.columns c
        LEFT JOIN information_schema.statistics s
            ON c.table_schema = DATABASE() 
            AND c.table_name = s.table_name 
            AND c.column_name = s.column_name 
            AND s.non_unique = 0
        WHERE c.table_schema = DATABASE() AND c.table_name = ?
        ORDER BY c.ordinal_position
    ", [$tableName]);

        // Chuyển đổi kết quả thành mảng và xử lý dữ liệu
        return array_map(function ($column) {
            return [
                'name' => $column->name,
                'type' => $column->type,
                'nullable' => $column->nullable === 'YES',
                'default_value' => $column->default_value,
                'comment' => $column->comment ?: '', // Xử lý null thành chuỗi rỗng
                'max_length' => $column->max_length,
                'extra_info' => $column->extra_info,
                'is_unique' => (bool) $column->is_unique, // Thêm cột kiểm tra UNIQUE
            ];
        }, $columns);
    }
}
