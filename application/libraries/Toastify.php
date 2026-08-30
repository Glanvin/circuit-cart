<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Toastify {

    protected $CI;

    public function __construct() {
        $this->CI = get_instance();
    }

    /**
     * Set a success toast message
     */
    public function success($message) {
        $this->CI->session->set_flashdata('toast_success', $message);
    }

    /**
     * Set an error toast message
     */
    public function error($message) {
        $this->CI->session->set_flashdata('toast_error', $message);
    }

    /**
     * Set an informational toast message
     */
    public function info($message) {
        $this->CI->session->set_flashdata('toast_info', $message);
    }

    /**
     * Render active flashdata as Toasts
     */
    public function render() {
        $types = [
            'toast_success' => ['class' => 'text-bg-success', 'title' => 'Success'],
            'toast_error'   => ['class' => 'text-bg-danger',  'title' => 'Error'],
            'toast_info'    => ['class' => 'text-bg-info',    'title' => 'Notice']
        ];

        $toasts = '';

        foreach ($types as $key => $value) {
            $message = $this->CI->session->flashdata($key);

            if ($message) {
                $toasts .= '
                <div class="toast ' . $value['class'] . ' border-0 fade show m-3" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header ' . $value['class'] . ' text-white border-bottom border-white border-opacity-25">
                        <strong class="me-auto">' . $value['title'] . '</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ' . htmlspecialchars($message) . '
                    </div>
                </div>';
            }
        }

        if (empty($toasts)) {
            return '';
        }

        return '
        <div class="toast-container top-0 end-0 z-3">
            ' . $toasts . '
        </div>';
    }
}