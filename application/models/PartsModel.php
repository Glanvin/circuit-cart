<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PartsModel extends CI_Model {

    //Initializes the model.
    public function __construct() {
        parent::__construct();
    }

    //Returns all active tech parts.
    public function getAllParts() {
        //Only fetch parts where is_active is 1
        $this->db->where('is_active', 1);
        $query = $this->db->get('tbl_sku');

        // heck if the query itself failed (syntax error, table missing)
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
                $categories[] = $row['category'];
            }
        }

        $this->db->select('device_type');
        $this->db->distinct();
        $this->db->order_by('device_type', 'ASC');
        $query = $this->db->get('tbl_sku');

        if ($query) {
            foreach ($query->result_array() as $row) {
                $deviceTypes[] = $row['device_type'];
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
        if ($delta === 0) {
            return $this->getPartById($part_id)['stock_quantity'];
        }

        $part = $this->getPartById($part_id);
        $newQty = (int) $part['stock_quantity'] + (int) $delta;

        if ($newQty < 0) {
            throw new AppException('D02', ['item' => $part['part_name']]);
        }

        $this->db->trans_start();

        $this->db->where('part_id', $part_id);
        $this->db->update('tbl_sku', ['stock_quantity' => $newQty]);

        $this->recordStockMovement($part_id, $delta, $reason, $reference_type, $reference_id, $employee_id, $note);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q14');
        }

        return $newQty;
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