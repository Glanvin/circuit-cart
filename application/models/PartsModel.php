<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PartsModel extends CI_Model {

    //Initializes the model.
    public function __construct() {
        parent::__construct();
    }

    //Only fetch sellable parts. Search, category, device and price filters are
    //applied server-side so the catalog remains usable as it grows.
    private function applyCatalogFilters(array $filters): void {
        $this->db->where('is_active', 1);
        if (!empty($filters['q'])) {
            $this->db->group_start();
            $this->db->like('part_name', $filters['q']);
            $this->db->or_like('sku', $filters['q']);
            $this->db->or_like('category', $filters['q']);
            $this->db->or_like('device_type', $filters['q']);
            $this->db->group_end();
        }
        if (!empty($filters['category'])) {
            $this->db->where('category', $filters['category']);
        }
        if (!empty($filters['device_type'])) {
            $this->db->where('device_type', $filters['device_type']);
        }
        //Server-side price range (D2). Only numeric bounds are honoured so a
        //forged non-numeric query string cannot alter the WHERE clause.
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $this->db->where('selling_price >=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $this->db->where('selling_price <=', (float) $filters['max_price']);
        }
    }

    //Applies a catalog sort whitelist to the active query.
    private function applyCatalogSort(string $sort): void {
        switch ($sort) {
            case 'price_asc': $this->db->order_by('selling_price', 'ASC'); break;
            case 'price_desc': $this->db->order_by('selling_price', 'DESC'); break;
            case 'name_desc': $this->db->order_by('part_name', 'DESC'); break;
            case 'newest': $this->db->order_by('created_at', 'DESC'); break;
            default: $this->db->order_by('part_name', 'ASC');
        }
    }

    //Total number of active parts matching the filters (for pagination).
    public function countAllParts(array $filters = []): int {
        $this->applyCatalogFilters($filters);
        return (int) $this->db->count_all_results('tbl_sku');
    }

    //Returns the active tech parts on a single page. Pass $page (1-based) to
    //limit results; omit it for the full result set.
    public function getAllParts(array $filters = [], string $sort = 'name_asc', ?int $page = NULL, int $perPage = 12) {
        $this->applyCatalogFilters($filters);
        $this->applyCatalogSort($sort);

        if ($page !== NULL && $page > 0) {
            $this->db->limit($perPage, ($page - 1) * $perPage);
        }

        $query = $this->db->get('tbl_sku');

        // Check if the query itself failed (syntax error, table missing)
        if (!$query) {
            throw new AppException('Q01');
        }

        return $query->result_array();
    }

    //Returns all tech parts (active and inactive) for the stock management page.
    //$filters: q (SKU/name search), category, device_type. $sort defaults to
    //stock descending; also accepts stock_asc, name_asc, name_desc, id_asc.
    public function getAllPartsAdmin(array $filters = [], string $sort = 'stock_desc') {
        if (!empty($filters['q'])) {
            $this->db->group_start();
            $this->db->like('sku', $filters['q']);
            $this->db->or_like('part_name', $filters['q']);
            $this->db->group_end();
        }

        if (!empty($filters['category'])) {
            $this->db->where('category', $filters['category']);
        }

        if (!empty($filters['device_type'])) {
            $this->db->where('device_type', $filters['device_type']);
        }

        switch ($sort) {
            case 'stock_asc':
                $this->db->order_by('stock_quantity', 'ASC');
                $this->db->order_by('part_name', 'ASC');
                break;
            case 'name_asc':
                $this->db->order_by('part_name', 'ASC');
                break;
            case 'name_desc':
                $this->db->order_by('part_name', 'DESC');
                break;
            case 'id_asc':
                $this->db->order_by('part_id', 'ASC');
                break;
            default:
                $this->db->order_by('stock_quantity', 'DESC');
                $this->db->order_by('part_name', 'ASC');
        }

        $query = $this->db->get('tbl_sku');

        if (!$query) {
            throw new AppException('Q01');
        }

        return $query->result_array();
    }

    //Related products for a detail page: same category or device type, the
    //product itself excluded, limited to $limit. Never throws so an empty
    //recommendation row degrades gracefully.
    public function getRelatedParts(int $part_id, string $category = '', string $device_type = '', int $limit = 4) {
        $limit = max(1, min(12, $limit));

        $this->db->where('is_active', 1);
        $this->db->where('part_id !=', $part_id);

        if ($category !== '' || $device_type !== '') {
            $this->db->group_start();
            $first = TRUE;
            if ($category !== '') {
                $this->db->where('category', $category);
                $first = FALSE;
            }
            if ($device_type !== '') {
                if (!$first) {
                    $this->db->or_where('device_type', $device_type);
                } else {
                    $this->db->where('device_type', $device_type);
                }
            }
            $this->db->group_end();
        }

        $this->db->order_by('RAND()');
        $this->db->limit($limit);

        $query = $this->db->get('tbl_sku');
        if (!$query) {
            return [];
        }

        return $query->result_array();
    }

    //Ordered gallery photos for a part (extra images beyond the primary
    //tbl_sku.image_path). Returns [] when the part has no extras or the query
    //fails, so a missing gallery never breaks a product page.
    public function getProductImages(int $part_id): array {
        $this->db->where('part_id', $part_id);
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get('tbl_product_images');
        if (!$query) {
            return [];
        }
        return $query->result_array();
    }

    //Inserts one gallery photo, placing it after the existing ones.
    public function addProductImage(int $part_id, string $image_path, ?int $sort_order = null): int {
        if ($image_path === '') {
            throw new AppException('D01');
        }
        if ($sort_order === null) {
            $sort_order = $this->nextGallerySort($part_id);
        }
        $ok = $this->db->insert('tbl_product_images', [
            'part_id' => $part_id,
            'image_path' => $image_path,
            'sort_order' => $sort_order,
        ]);
        if (!$ok) {
            throw new AppException('Q16');
        }
        return (int) $this->db->insert_id();
    }

    //Next free sort_order for a part (max + 1, or 0 when empty).
    private function nextGallerySort(int $part_id): int {
        $this->db->select('MAX(sort_order) AS max_order', FALSE);
        $this->db->where('part_id', $part_id);
        $query = $this->db->get('tbl_product_images');
        if (!$query) {
            return 0;
        }
        $row = $query->row_array();
        return isset($row['max_order']) && $row['max_order'] !== NULL ? ((int) $row['max_order'] + 1) : 0;
    }

    //Deletes one gallery photo by primary key. Returns whether a row went away.
    public function deleteProductImage(int $id): bool {
        return (bool) $this->db->delete('tbl_product_images', ['id' => $id]);
    }

    //Distinct category and device_type values for the inventory filter dropdowns.
    public function stockFilterOptions(): array {
        $categories = [];
        $deviceTypes = [];

        $this->db->select('category');
        $this->db->distinct();
        $this->db->order_by('category', 'ASC');
        $query = $this->db->get('tbl_sku');

        if ($query) {
            foreach ($query->result_array() as $row) {
                if (isset($row['category']) && $row['category'] !== '') {
                    $categories[] = $row['category'];
                }
            }
        }

        $this->db->select('device_type');
        $this->db->distinct();
        $this->db->order_by('device_type', 'ASC');
        $query = $this->db->get('tbl_sku');

        if ($query) {
            foreach ($query->result_array() as $row) {
                if (isset($row['device_type']) && $row['device_type'] !== '') {
                    $deviceTypes[] = $row['device_type'];
                }
            }
        }

        return ['categories' => $categories, 'device_types' => $deviceTypes];
    }

    //Creates a new inventory item.
    public function createSku($data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        if (!$this->db->insert('tbl_sku', $data)) {
            throw new AppException('Q01');
        }

        return $this->db->insert_id();
    }

    //Updates an inventory item's details.
    public function updateSku($part_id, $data) {
        if (empty($data)) {
            throw new AppException('D01');
        }

        $this->db->where('part_id', $part_id);
        return $this->db->update('tbl_sku', $data);
    }

    //Sets the absolute stock quantity for a part.
    public function setStock($part_id, $quantity) {
        $this->db->where('part_id', $part_id);
        return $this->db->update('tbl_sku', ['stock_quantity' => (int) $quantity]);
    }
    
    //Returns a single tech part by its 6-digit ID.
    public function getPartById($part_id) {
        $this->db->where('part_id', $part_id);
        $query = $this->db->get('tbl_sku');

        //Check if the query failed
        if (!$query) {
            throw new AppException('Q02', ['id' => $part_id]);
        }

        //Check if the part actually exists
        if ($query->num_rows() === 0) {
            throw new AppException('P02', ['id' => $part_id]);
        }

        return $query->row_array();
    }

    //----------------------------------------------------------------------
    // Stock Movements
    //----------------------------------------------------------------------

    //Records a stock movement.
    //delta: positive for increase, negative for decrease
    //reason: sale, adjustment, receive, repair_use, return, count, write_off
    public function recordStockMovement($part_id, $delta, $reason, $reference_type = NULL, $reference_id = NULL, $employee_id = NULL, $note = NULL) {
        if ($delta === 0) {
            return TRUE; //No movement needed
        }

        $movement = [
            'part_id' => $part_id,
            'qty_delta' => (int) $delta,
            'reason' => $reason,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'employee_id' => $employee_id,
            'note' => $note,
        ];

        return $this->db->insert('tbl_stock_movements', $movement);
    }

    //Adjusts stock by a delta amount and records the movement.
    //Returns the new stock quantity.
    public function adjustStock($part_id, $delta, $reason, $reference_type = NULL, $reference_id = NULL, $employee_id = NULL, $note = NULL) {
        $delta = (int) $delta;

        if ($delta === 0) {
            return $this->getPartById($part_id)['stock_quantity'];
        }

        $this->db->trans_start();

        try {
            //Apply the delta in SQL so concurrent sales/repairs cannot both
            //pass a stale read and drive stock below zero. The movement is in
            //the same transaction as the quantity change.
            $this->db->set('stock_quantity', 'stock_quantity + ' . $delta, FALSE);
            $this->db->where('part_id', $part_id);
            if ($delta < 0) {
                $this->db->where('stock_quantity >=', -$delta);
            }
            $updated = $this->db->update('tbl_sku');

            if (!$updated) {
                throw new AppException('Q14');
            }

            if ($this->db->affected_rows() === 0) {
                //Fetch only on failure to distinguish an unknown SKU from an
                //existing SKU that no longer has enough stock.
                $part = $this->getPartById($part_id);
                throw new AppException('D02', ['item' => $part['part_name']]);
            }

            if (!$this->recordStockMovement($part_id, $delta, $reason, $reference_type, $reference_id, $employee_id, $note)) {
                throw new AppException('Q14');
            }

            //Read before completing the transaction so this return value
            //reflects the quantity written by this transaction.
            $part = $this->getPartById($part_id);
            $this->db->trans_complete();
        } catch (Exception $e) {
            //Balance this transaction level before callers unwind or roll back
            //their surrounding transaction.
            $this->db->trans_complete();
            throw $e;
        }

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q14');
        }

        return (int) $part['stock_quantity'];
    }

    //Gets stock movements for a part.
    public function getStockMovements($part_id, $limit = 100) {
        $this->db->where('part_id', $part_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get('tbl_stock_movements');
        if (!$query) {
            throw new AppException('Q14');
        }
        return $query->result_array();
    }

    //Gets all stock movements with filters.
    public function getAllStockMovements($filters = [], $limit = 200) {
        if (!empty($filters['part_id'])) {
            $this->db->where('part_id', $filters['part_id']);
        }
        if (!empty($filters['reason'])) {
            $this->db->where('reason', $filters['reason']);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $query = $this->db->get('tbl_stock_movements');
        if (!$query) {
            throw new AppException('Q14');
        }
        return $query->result_array();
    }
}
