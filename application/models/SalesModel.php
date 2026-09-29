<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SalesModel extends CI_Model {

    //Fixed online order fees, matching the values shown on the cart/checkout UI.
    private const ONLINE_SHIPPING = 10.00;
    private const ONLINE_TAX = 20.00;

    public function __construct() {
        parent::__construct();
    }

    //Records a sale inside a DB transaction and decreases stock for each line.
    //$data: origin, sold_by, customer_email, customer_address, items
    //  ([part_id, qty]), tendered (store only). Prices/totals are computed
    //  server-side from the part records; the client never sets them.
    public function recordSale(array $data) {
        if (empty($data) || empty($data['items']) || !is_array($data['items'])) {
            throw new AppException('D01');
        }

        $origin = $data['origin'] === 'online' ? 'online' : 'store';

        $lines = [];
        $subtotal = 0.0;

        foreach ($data['items'] as $line) {
            $part_id = (int) ($line['part_id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);

            if ($part_id <= 0 || $qty <= 0) {
                throw new AppException('D01');
            }

            $part = $this->PartsModel->getPartById($part_id);

            if ((int) $part['stock_quantity'] < $qty) {
                throw new AppException('D02', ['item' => $part['part_name']]);
            }

            $lines[] = [
                'part_id' => (int) $part['part_id'],
                'name' => $part['part_name'],
                'qty' => $qty,
                'price' => (float) $part['selling_price'],
            ];

            $subtotal += (float) $part['selling_price'] * $qty;
        }

        $subtotal = round($subtotal, 2);
        $fees = $origin === 'online' ? self::ONLINE_SHIPPING + self::ONLINE_TAX : 0.0;
        $total = round($subtotal + $fees, 2);

        $sale = [
            'receipt_no' => $this->generateReceipt($origin),
            'origin' => $origin,
            'status' => $origin === 'online' ? 'processing' : 'processing',
            'sold_by' => $data['sold_by'] ?? NULL,
            'customer_email' => $data['customer_email'] ?? NULL,
            'customer_address' => $data['customer_address'] ?? NULL,
            'items' => json_encode($lines),
            'subtotal' => $subtotal,
            'total' => $total,
            'tendered' => $origin === 'store' ? (float) ($data['tendered'] ?? $total) : NULL,
        ];

        $this->db->trans_start();

        foreach ($lines as $line) {
            $this->db->set('stock_quantity', 'stock_quantity - ' . $line['qty'], FALSE);
            $this->db->where('part_id', $line['part_id']);
            $this->db->update('tbl_sku');
        }

        $this->db->insert('tbl_sales', $sale);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q08');
        }

        $sale['total'] = $total;

        return $sale;
    }

    public function getSaleById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P08', ['id' => $id]);
        }

        return $query->row_array();
    }

    //Total sales grouped by day (2026-09-01) for a given origin. Pass a
    //status filter to restrict further (e.g. 'delivered' for online orders).
    public function getDayTotals(string $from, string $origin, $status = NULL) {
        $this->db->select('DATE(created_at) AS day, SUM(total) AS total');
        $this->db->where('created_at >=', $from);
        $this->db->where('origin', $origin);

        if ($status !== NULL) {
            $this->db->where('status', $status);
        }

        $this->db->group_by('day');
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        $rows = [];

        foreach ($query->result_array() as $row) {
            $rows[$row['day']] = (float) $row['total'];
        }

        return $rows;
    }

    //Online orders for the fulfilment panel. Newest placed date first by
    //default; pass one of newest|oldest|total_high|total_low to change it.
    //$q runs a case-insensitive match against the receipt, customer email,
    //customer address, and the staff who recorded the sale.
    public function getOnlineSales($status = NULL, $sort = 'newest', $q = '') {
        $this->db->where('origin', 'online');

        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        if (trim($q) !== '') {
            $this->db->group_start();
            $this->db->like('receipt_no', $q);
            $this->db->or_like('customer_email', $q);
            $this->db->or_like('customer_address', $q);
            $this->db->or_like('sold_by', $q);
            $this->db->group_end();
        }

        switch ($sort) {
            case 'oldest':
                $this->db->order_by('created_at', 'ASC');
                $this->db->order_by('id', 'ASC');
                break;
            case 'total_high':
                $this->db->order_by('total', 'DESC');
                $this->db->order_by('created_at', 'DESC');
                break;
            case 'total_low':
                $this->db->order_by('total', 'ASC');
                $this->db->order_by('created_at', 'ASC');
                break;
            default:
                $this->db->order_by('created_at', 'DESC');
                $this->db->order_by('id', 'DESC');
        }

        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        return $query->result_array();
    }

    public function updateStatus($id, $status) {
        $this->db->where('id', $id);
        return $this->db->update('tbl_sales', ['status' => $status]);
    }

    //Unique human-readable receipt number, e.g. ST-20260924-1F8A9 or
    //ON-20260924-7C2D3.
    private function generateReceipt(string $origin): string {
        $prefix = $origin === 'store' ? 'ST' : 'ON';
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}